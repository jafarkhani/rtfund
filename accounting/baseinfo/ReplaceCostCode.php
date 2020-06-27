<?php
//---------------------------
// developer:	Sh.Jafarkhani
// Date:		97.05
//---------------------------
require_once '../../header.inc.php';

?>

<script type="text/javascript">

ReplaceCostCode.prototype={
	TabID : '<?= $_REQUEST["ExtTabID"] ?>',
	address_prefix : "<?= $js_prefix_address ?>",

	get : function(elementID){
		return findChild(this.TabID, elementID);
	}
}

function ReplaceCostCode(){

	this.formPanel = new Ext.form.Panel({
		applyTo: this.get("mainform"),
		title : "جابجایی کدهای حساب",
		width: 630,
		frame : true,
		items: [{
			xtype : "combo",
			width : 610,
			fieldLabel : "کد حساب قدیمی",
			store: new Ext.data.Store({
				fields:["CostID","CostCode","CostDesc", "TafsiliType1","TafsiliType2",{
					name : "fullDesc",
					convert : function(value,record){
						return "[ " + record.data.CostCode + " ] " + record.data.CostDesc
					}				
				}],
				proxy: {
					type: 'jsonp',
					url: this.address_prefix + '../baseinfo/baseinfo.data.php?task=SelectCostCode',
					reader: {root: 'rows',totalProperty: 'totalCount'}
				}
			}),
			typeAhead: false,
			name : "OLD_CostID",
			valueField : "CostID",
			displayField : "fullDesc"			
		},{
			xtype : "combo",
			width : 610,
			fieldLabel : "کد حساب جدید",
			store: new Ext.data.Store({
				fields:["CostID","CostCode","CostDesc", "TafsiliType1","TafsiliType2",{
					name : "fullDesc",
					convert : function(value,record){
						return "[ " + record.data.CostCode + " ] " + record.data.CostDesc
					}				
				}],
				proxy: {
					type: 'jsonp',
					url: this.address_prefix + '../baseinfo/baseinfo.data.php?task=SelectCostCode',
					reader: {root: 'rows',totalProperty: 'totalCount'}
				}
			}),
			typeAhead: false,
			name : "NEW_CostID",
			valueField : "CostID",
			displayField : "fullDesc"
		}],
		buttons: [{
			text : "ذخیره",
			iconCls : "save",
			handler: function(){

				mask = new Ext.LoadMask(ReplaceCostCodeObj.formPanel, {msg:'در حال ذخيره سازي...'});
				mask.show();

				ReplaceCostCodeObj.formPanel.getForm().submit({
					url:  ReplaceCostCodeObj.address_prefix + 'baseinfo.data.php?task=ReplaceCostCodes',
					method : "POST",
					clientValidation : true,

					success : function(form,action){
						Ext.MessageBox.alert("", "تعداد " + action.result.data + 
							" رکورد در اسناد اصلاح گردید");
						mask.hide();
					}
					,
					failure : function(form,action){                                  
						alert(action.result.data);
						mask.hide();
					}
				});								
			}
		}]
	});
}

var ReplaceCostCodeObj = new ReplaceCostCode();
	
</script>
<center>
	<div><div id="mainform"></div>
	<br>	
	</div>
	<div id="divCost"></div>
</center>
