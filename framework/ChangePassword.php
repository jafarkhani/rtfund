<?php
//-----------------------------
//	Programmer	: SH.Jafarkhani
//	Date		: 94.06
//-----------------------------
require_once '../header.inc.php';
require_once 'md5.php';
?>
<script>
ChangePass.prototype = {
	TabID : '<?= $_REQUEST["ExtTabID"]?>',
	address_prefix : "<?= $js_prefix_address?>",

	get : function(elementID){
		return findChild(this.TabID, elementID);
	}
};

function ChangePass()
{
	Ext.form.Field.prototype.msgTarget = 'side';

	Ext.apply(Ext.form.VTypes, {

		ChangePass : function(val, field) {
			if (field.initialPassField) {
				var pwd = ChangePassObject.passform.getComponent(field.initialPassField);
				return (val == pwd.getValue());
			}
			return true;
		},
		ChangePassText : 'رمزهای عبور جدید یکسان نمی باشند'
	});

	this.passform = new Ext.form.FormPanel({
		renderTo : "pass_div",
		frame: true,
		title: 'تغییر رمز عبور',
		bodyStyle:'padding:5px 5px 0px',
		width: 400,
		defaults: {
			inputType: 'password',
			allowBlank:false
		},
		defaultType: 'textfield',
		items: [{
			fieldLabel: 'رمز عبور فعلی',
			name: 'cur_pass',
			itemId: 'cur_pass'
		},{
			fieldLabel: 'رمز عبور جدید',
			name: 'new_pass',
			minLength : 8,
			itemId: 'new_pass'
		},{
			fieldLabel: 'تکرار رمز عبور جدید',
			name: 'new_pass2',
			itemId: 'new_pass2',
			minLength : 8,
			vtype: 'ChangePass',
			initialPassField: 'new_pass' // id of the initial ChangePass field
		}],

		buttons : [{
			text : "ذخیره",
			iconCls: 'save',
			handler: function() {
				if (ChangePassObject.passform.form.isValid()) {
					
					me = ChangePassObject;
					var mask = new Ext.LoadMask(Ext.getCmp(me.TabID),{msg: 'تغییر رمز عبور ...'});
					mask.show();

					Ext.Ajax.request({
						url: '/framework/person/persons.data.php' , 
						params: {
							task: "changePass",
							cur_pass : MD5(me.passform.getComponent("cur_pass").getValue()), 
							new_pass : MD5(me.passform.getComponent("new_pass").getValue())
						},
						method: "POST",

						success : function(response,options)
						{
							mask.hide();
							result = Ext.decode(response.responseText);
							if(result.success)
							{
								me = ChangePassObject;
								Ext.MessageBox.alert("","رمز شما با موفقیت تغییر یافت");
								me.passform.getForm().reset();
							}
							else
							{
								if(result.data == "CurPassError")
									Ext.MessageBox.alert("","رمز عبور فعلی اشتباه می باشد");
								else
									Ext.MessageBox.alert("","عملیات مورد نظر با شکست مواجه شد");
							}
						},
						failure : function(response)
						{
							Ext.MessageBox.alert("","عملیات مورد نظر با شکست مواجه شد");
							mask.hide();
						}
					});
				}
			}

		}]
	});

	this.passform.center();
}

ChangePassObject = new ChangePass();

</script>
<br>
<div id="pass_div"></div>
