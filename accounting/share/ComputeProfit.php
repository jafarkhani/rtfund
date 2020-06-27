<?php
//-----------------------------
//	Programmer	: SH.Jafarkhani
//	Date		: 94.12
//-----------------------------

require_once '../../header.inc.php';

//................  GET ACCESS  .....................
$accessObj = FRW_access::GetAccess($_POST["MenuID"]);
//...................................................

?>
<script>
ComputeProfit.prototype = {
	TabID : '<?= $_REQUEST["ExtTabID"]?>',
	address_prefix : "<?= $js_prefix_address?>",

	AddAccess : <?= $accessObj->AddFlag ? "true" : "false" ?>,
	EditAccess : <?= $accessObj->EditFlag ? "true" : "false" ?>,
	RemoveAccess : <?= $accessObj->RemoveFlag ? "true" : "false" ?>,
	
	DocID : "",

	get : function(elementID){
		return findChild(this.TabID, elementID);
	}
}

function ComputeProfit()
{
	this.mainPanel = new Ext.form.Panel({
		renderTo : this.get("main"),
		frame : true,
		bodyStyle : "text-align:right;padding:5px",
		title : "صدور سند محاسبه سود سهام سهامداران",
		width : 500,
		items :[{
			xtype : "currencyfield",
			fieldLabel : "سود سهام ویژه",
			allowBlank : false,
			name : "TotalProfit",
			hideTrigger : true
		},{
			xtype : "combo",
			colspan : 2,
			width : 400,
			store : new Ext.data.SimpleStore({
				proxy: {
					type: 'jsonp',
					url: "/accounting/global/domain.data.php?task=GetAccessBranches",
					reader: {root: 'rows',totalProperty: 'totalCount'}
				},
				fields : ['BranchID','BranchName'],
				autoLoad : true					
			}),
			fieldLabel : "صدور سند در شعبه",
			queryMode : 'local',
			value : "<?= !isset($_SESSION["accounting"]["BranchID"]) ? "" : $_SESSION["accounting"]["BranchID"] ?>",
			displayField : "BranchName",
			valueField : "BranchID",
			name : "BranchID"
		}],
		buttons : [{
			text : "صدور سند سود سهام",
			disabled : this.AddAccess ? false : true,
			iconCls : "account",
			handler : function()
			{
				var mask = new Ext.LoadMask(this.up('form'), {msg:'در حال ذخيره سازي...'});
				mask.show();
				
				var form = this.up('form').getForm();
				form.submit({
					clientValidation: true,
					url: ComputeProfitObj.address_prefix + '../docs/doc.data.php',
					params : {
						task : "ComputeDoc",
						ComputeType : "ShareProfit"
					},
					method : "post",
					
					success: function(fp, o) {
						mask.hide();
						ComputeProfitObj.DocID = o.result.data;
						ComputeProfitObj.mainPanel.down("[itemId=btn_cancel]").enable();	
						ComputeProfitObj.DocPanel.show();
						ComputeProfitObj.DocPanel.loader.load({
							params :{
								DocID : ComputeProfitObj.DocID
							}
						});
					},
					failure : function(fp, o){
						Ext.MessageBox.alert("",o.result.data);
						mask.hide();
					}
				});
			}
		},{
			text : "ابطال سند صادر شده",
			disabled : this.AddAccess ? false : true,
			iconCls : "undo",
			itemId : "btn_cancel",
			disabled : true,
			handler : function()
			{
				var mask = new Ext.LoadMask(this.up('form'), {msg:'در حال ذخيره سازي...'});
				mask.show();
				
				Ext.Ajax.request({
					url: ComputeProfitObj.address_prefix + '../docs/doc.data.php?task=removeDoc',
					method : "post",
					params :{
						DocID : ComputeProfitObj.DocID
					},
					
					success: function(response) {
						result = Ext.decode(response.responseText);
						ComputeProfitObj.mainPanel.down("[itemId=btn_cancel]").disable();	
						ComputeProfitObj.DocPanel.hide();
						mask.hide();
					}
				});
			}
		}]
	});
	
	this.DocPanel = new Ext.form.Panel({
		renderTo : this.get("regDocDIV"),
		frame : true,
		width : 800,
		height : 500,
		autoScroll : true,
		hidden : true,
		title : "سند صادر شده",
		loader : {
			url : this.address_prefix + "../docs/print_doc.php"
		}
	});
}

ComputeProfitObj = new ComputeProfit();


</script>
<form id="mainForm">
	<center><br>
		<div id="main" ></div><br>
		<div><div id="regDocDIV"></div></div>
	</center>
</form>