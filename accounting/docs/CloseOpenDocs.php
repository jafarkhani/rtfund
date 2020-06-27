<?php
//-----------------------------
//	Programmer	: SH.Jafarkhani
//	Date		: 94.06
//-----------------------------

require_once '../../header.inc.php';

//................  GET ACCESS  .....................
$accessObj = FRW_access::GetAccess($_POST["MenuID"]);
//...................................................

?>
<script>
CloseOpenDoc.prototype = {
	TabID : '<?= $_REQUEST["ExtTabID"]?>',
	address_prefix : "<?= $js_prefix_address?>",

	AddAccess : <?= $accessObj->AddFlag ? "true" : "false" ?>,
	EditAccess : <?= $accessObj->EditFlag ? "true" : "false" ?>,
	RemoveAccess : <?= $accessObj->RemoveFlag ? "true" : "false" ?>,
	
	get : function(elementID){
		return findChild(this.TabID, elementID);
	}
}

function CloseOpenDoc()
{
	this.form2 = new Ext.form.Panel({
		renderTo : this.get("regDocDIV2"),
		frame : true,
		bodyStyle : "text-align:right;padding:5px",
		title : "صدور سند افتتاحیه و اختتامیه",
		width : 450,
		items :[{
			xtype : "combo",
			width : 400,
			store : new Ext.data.Store({
				proxy:{
					type: 'jsonp',
					url: '/framework/baseInfo/baseInfo.data.php?task=SelectBranches',
					reader: {root: 'rows',totalProperty: 'totalCount'}
				},
				fields :  ["BranchID", "BranchName"]
			}),
			displayField: 'BranchName',
			valueField : "BranchID",
			name : "BranchID",
			fieldLabel : "شعبه سند"
		},{
			xtype : "numberfield",
			name : "LocalNo",
			fieldLabel : "شماره سند",		
			hideTrigger : true
		},{
			xtype : "fieldset",
			html : "توجه : در صورتیکه شماره سند را وارد نکنید، شماره آخرین سند در نظر گرفته می شود"
		}],
		buttons : [{
			text : "صدور سند بستن حسابهای موقت",
			disabled : this.AddAccess ? false : true,
			iconCls : "account",
			handler : function()
			{
				mask = new Ext.LoadMask(this.up('form'), {msg:'در حال ذخيره سازي...'});
				mask.show();
				
				var form = this.up('form').getForm();
				form.submit({
					clientValidation: true,
					url: CloseOpenDocObj.address_prefix + 'doc.data.php?task=RegisterCloseDoc',
					success: function(fp, o) {
						if(o.result.success)
							Ext.MessageBox.alert("","سند با موفقیت صادر شد");
						else
							Ext.MessageBox.alert("",o.result.data);
						
						mask.hide();
					},
					failure : function(fp, o){
						Ext.MessageBox.alert("",o.result.data);
						mask.hide();
					}
				});
			}	
		},{
			text : "صدور سند اختتامیه",
			disabled : this.AddAccess ? false : true,
			iconCls : "account",
			handler : function()
			{
				mask = new Ext.LoadMask(this.up('form'), {msg:'در حال ذخيره سازي...'});
				mask.show();
				
				var form = this.up('form').getForm();
				form.submit({
					clientValidation: true,
					url: CloseOpenDocObj.address_prefix + 'doc.data.php?task=RegisterEndDoc',
					success: function(fp, o) {
						if(o.result.success)
							Ext.MessageBox.alert("","سند با موفقیت صادر شد");
						else
							Ext.MessageBox.alert("",o.result.data);
						
						mask.hide();
					},
					failure : function(fp, o){
						Ext.MessageBox.alert("",o.result.data);
						mask.hide();
					}
				});
			}
		},{
			text : "صدور سند افتتاحیه",
			disabled : this.AddAccess ? false : true,
			iconCls : "account",
			handler : function()
			{
				var mask = new Ext.LoadMask(this.up('form'), {msg:'در حال ذخيره سازي...'});
				mask.show();
				
				var form = this.up('form').getForm();
				form.submit({
					clientValidation: true,
					url: CloseOpenDocObj.address_prefix + 'doc.data.php?task=RegisterStartDoc',
					success: function(fp, o) {
						if(o.result.success)
							Ext.MessageBox.alert("","سند با موفقیت صادر شد");
						else
							Ext.MessageBox.alert("",o.result.data);
						
						mask.hide();
					},
					failure : function(fp, o){
						Ext.MessageBox.alert("",o.result.data);
						mask.hide();
					}
				});
			}
		}]
	});
}

CloseOpenDocObj = new CloseOpenDoc();


</script>
<form id="mainForm">
	<center><br>
		<div id="main" ></div><br><br>
		<div id="regDocDIV"></div><br>
		<div id="regDocDIV2"></div><br>
	</center>
</form>