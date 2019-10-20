<?php
//---------------------------
// programmer:	Sh.Jafarkhani
// create Date:	97.11
//---------------------------
require_once '../header.inc.php';

?>
<center>	
	<br>
    <form id="mainForm">
		<div id="div_form"></div>
	</form>
</center>
<script type="text/javascript">

process_rundaily.prototype = {
	TabID: '<?= $_REQUEST["ExtTabID"] ?>',
	address_prefix : "<?= $js_prefix_address ?>",

	get : function(elementID){
		return findChild(this.TabID, elementID);
	}
};

function process_rundaily(){	
	
	this.wizardPanel = new Ext.form.Panel({
		width: 500,
		autoHeight : true,			
		id : "card-wizard-panel",
		title: "اجرای ثبت های روزانه تسهیلات",
		renderTo: this.get("div_form"),
		frame: true,
		activeItem: 0, 
		items: [{
			xtype : "numberfield",
			fieldLabel : "شماره وام",
			name : "RequestID",
			hideTrigger : true
		},{
			xtype : "shdatefield",
			fieldLabel : "از تاریخ",
			name : "fdate",
			allowBlank : false
		},{
			xtype : "shdatefield",
			fieldLabel : "تا تاریخ",
			name : "tdate",
			allowBlank : false
		}],
		buttons: [{
			text: 'اجرا',
			handler: function(){
				process_rundailyObject.Run();
			}
		}]
	});
	
}

process_rundaily.prototype.Run = function(){
	
	if(!this.wizardPanel.getForm().isValid())
		return;
	
	RequestID = this.wizardPanel.down("[name=RequestID]").getValue();
	
	window.open(this.address_prefix + "98dailyRegister.php?" +
			"fdate=" + this.wizardPanel.down("[name=fdate]").getRawValue() + 
			"&tdate=" +	this.wizardPanel.down("[name=tdate]").getRawValue() +
			(RequestID*1>0 ? "&RequestID=" + RequestID : "" ));
	return;
	
	mask = new Ext.LoadMask(Ext.getCmp(this.TabID),{msg:'در حال ذخیره سازی ...'});
	mask.show();

	Ext.Ajax.request({
		url: this.address_prefix + 'process.data.php',
		method: "POST",
		form : this.get("mainForm"),
		params: {
			task: "RunDaily"			
		},
		success: function(response){
			mask.hide();
			var st = Ext.decode(response.responseText);

			if(st.success)					
			{
				Ext.MessageBox.alert("","عملیات با موفقیت انجام شد");
				window.open('../storage/loanDaily.html');
			}
			else
			{
				if(st.data == "")
					Ext.MessageBox.alert("","خطا در اجرای عملیات");
				else
					Ext.MessageBox.alert("",st.data);
			}
		},
		failure: function(){
			mask.hide();
			Ext.MessageBox.alert("","خطا در اجرای عملیات");
		}
	});
}

var process_rundailyObject = new process_rundaily();

</script>
