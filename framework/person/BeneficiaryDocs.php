<?php
//-----------------------------
//	Programmer	: SH.Jafarkhani
//	Date		: 94.12
//-----------------------------
require_once '../../header.inc.php';
?>
<script>

BeneficiaryDocs.prototype = {
	TabID : '<?= $_REQUEST["ExtTabID"]?>',
	address_prefix : "<?= $js_prefix_address?>",

	get : function(elementID){
		return findChild(this.TabID, elementID);
	}
};

function BeneficiaryDocs()
{
	this.groupPnl = new Ext.form.FieldSet({
		renderTo: this.get("div_selectGroup"),
		title: "انتخاب ذینفع",
		width: 450, 
		items : [{
			xtype : "combo",
			store : new Ext.data.SimpleStore({
				data : [
					["Staff" , 'همکاران صندوق'],
					["Customer" , "مشتری" ],
					["Shareholder" , "سهامدار" ],
					["Agent" , 'سرمایه گذار' ],
					["Supporter", "حامی"],
					["Expert", 'کارشناس خارج از صندوق']
				],
				fields : ['TypeID','desc']
			}),
			valueField : "TypeID",
			width : 380,
			displayField : "desc",
			fieldLabel : "انتخاب ذینفع",
			listeners :{
				select : function(){
					me = BeneficiaryDocsObject;
					me.MainFrame.loader.load({
						scripts : true,
						params : {
							ExtTabID : me.TabID,
							ObjectType : "BeneficiaryDocs",
							ObjectID : this.getValue()
						}
					});
				}
			}
		}]
	});	
	
	this.MainFrame = new Ext.panel.Panel({
		width : 700,
		border : false,
		frame : false,
		renderTo : this.get("AttachPanel"),
		loader : {
			url : "../../office/dms/documents.php",
			scripts : true
		}
	});
}

var BeneficiaryDocsObject = new BeneficiaryDocs();

</script>
<center>
	<div id="div_selectGroup"></div>
	<div id="AttachPanel"></div>
</center>