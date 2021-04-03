<script type="text/javascript">
//-----------------------------
//	Programmer	: Mokhtari
//	Date		: 98.06
//-----------------------------

Request.prototype = {
	TabID : '<?= $_REQUEST["ExtTabID"]?>',
	address_prefix : "<?= $js_prefix_address?>",

	get : function(elementID){
		return findChild(this.TabID, elementID);
	}
};

function Request()
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

    Request.deleteRender = function(v,p,r)
    {
        /*if(r.data.IsActive == "NO")
        return "";*/
        return "<div align='center' title='حذف درخواست' class='remove' onclick='RequestObject.Deleting();' " +
        "style='background-repeat:no-repeat;background-position:center;" +
        "cursor:pointer;width:100%;height:16'></div>";
    }

Request.OpenLetter = function(LetterID){

    framework.OpenPage('/office/letter/LetterInfo.php','مشخصات نامه',
        {LetterID : LetterID});
}
    Request.ParamValueRender = function(v,p,r){

    if(r.data.LetterID != 0 && r.data.LetterID != null)
    /*return "<a href=javascript:void() onclick=Request.OpenLetter("+v+");>شماره نامه : "+v+"</a>";*/
    return "<a href=javascript:void() onclick=Request.OpenLetter("+v+");> "+v+"</a>";

}

    Request.prototype.Deleting = function()
    {
        var record = this.grid.getSelectionModel().getLastSelected();

        Ext.MessageBox.confirm("","آيا مايل به حذف مي باشيد؟", function(btn){
        if(btn == "no")
        return;

        Ext.Ajax.request({
        url : RequestObject.address_prefix + "Request.data.php",
        method : "POST",
        params : {
        task : "DeleteRequest",
        IDReq : record.data.IDReq
    },
        success : function(response,o)
    {
        RequestObject.grid.getStore().load();
    }
    });
    });
    }
    
    Request.prototype.ShowCheckList = function(){

    if(!this.CostsWin)
{
    this.CostsWin = new Ext.window.Window({
    title: 'چک لیست',
    modal : true,
    autoScroll : true,
    width: 800,
    height : 400,
    bodyStyle : "background-color:white",
    closeAction : "hide",
    loader : {
    url : "baseInfo/checkRequestValues.php",
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
    Ext.getCmp(this.TabID).add(this.CostsWin);
}
    this.CostsWin.show();
    this.CostsWin.center();
    this.CostsWin.loader.load({
    params : {
    MenuID : this.MenuID,
    ExtTabID : this.CostsWin.getEl().id,
    SourceID : this.grid.getSelectionModel().getLastSelected().data.IDReq,
    SourceType : <?= SOURCETYPE_Request ?>
}
});
}




    Request.OperationRender = function(value, p, record){

    return "<div title='عملیات' class='setting' onclick='RequestObject.OperationMenu(event);' " +
    "style='background-repeat:no-repeat;background-position:center;" +
    "cursor:pointer;width:100%;height:16'></div>";
    }
    Request.prototype.OperationMenu = function(e)
    {
        console.log(1111111111);
        var record = this.grid.getSelectionModel().getLastSelected();
        var op_menu = new Ext.menu.Menu();
        op_menu.add({text: 'حذف درخواست',iconCls: 'remove',
        handler : function(){ return RequestObject.Deleting(); }});

        op_menu.add({text: 'چک لیست',iconCls: 'check',
        handler : function(){ return RequestObject.ShowCheckList(); }});

        /*if(record.data.StatusID == "1")
    {
        console.log(2222222222);
        if(this.RemoveAccess)
        op_menu.add({text: 'حذف درخواست',iconCls: 'remove',
        handler : function(){ return RequestObject.Deleting(); }});
    }*/

        op_menu.showAt(e.pageX-120, e.pageY);

    }















</script>
