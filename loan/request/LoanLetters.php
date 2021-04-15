<?php
//-------------------------
// programmer:	Jafarkhani
// Create Date:	95.07
//-------------------------
require_once('../../header.inc.php');
require_once inc_dataGrid;
require_once './request.class.php';
//................  GET ACCESS  .....................
$accessObj = FRW_access::GetAccess($_POST["MenuID"]);
//...................................................

$RequestID = $_REQUEST["RequestID"];

$dg = new sadaf_datagrid("dg",$js_prefix_address . "request.data.php?task=GetLoanLetters&RequestID=" .$RequestID,"grid_div");

$dg->addColumn("", "RowID","", true);
$dg->addColumn("", "RequestID","", true);
$dg->addColumn("", "TemplateID","", true);

$col = $dg->addColumn("قالب نامه", "TemplateDesc");

$col = $dg->addColumn("شماره نامه", "LetterID");
$col->renderer = "LoanLetter.LettersRender";

$col = $dg->addColumn("تاریخ صدور", "RegDate", GridColumn::ColumnType_date);
$col->width = 100;

$col = $dg->addColumn("صادر کننده", "RegPersonName");
$col->width = 120;

$dg->addButton("", "صدور نامه", "letter", "function(){LoanLetterObject.BeforeSaveLetter()}");

$dg->height = 336;
$dg->emptyTextOfHiddenColumns = true;
$dg->EnableSearch = false;
$dg->HeaderMenu = false;
$dg->EnablePaging = false;
$dg->DefaultSortField = "RegDate";
$dg->DefaultSortDir = "ASC";
$dg->autoExpandColumn = "TemplateDesc";

$grid = $dg->makeGrid_returnObjects();

?>
<script type="text/javascript">

LoanLetter.prototype = {
	TabID : '<?= $_REQUEST["ExtTabID"]?>',
	address_prefix : "<?= $js_prefix_address?>",
	
	RequestID : <?= $RequestID ?>,
	
	get : function(elementID){
		return findChild(this.TabID, elementID);
	}
};

function LoanLetter()
{
	this.grid = <?= $grid ?>;
	this.grid.render(this.get("div_grid"));	
}

LoanLetter.LettersRender = function(value, p, record){
	
	return "<a href='javascript:void(0)' onclick=LoanLetter.OpenLetter("+value+")>" + value + "<a>";
}

LoanLetter.OpenLetter = function(LetterID){
	
	framework.OpenPage("/office/letter/LetterInfo.php", "مشخصات نامه", 
	{
		LetterID : LetterID
	});
}

LoanLetter.prototype.BeforeSaveLetter = function(record){
	
	if(!this.TemplateWin)
	{
		this.TemplateWin = new Ext.window.Window({
			width : 400,
			height : 100,
			bodyStyle : "background-color:white",
			modal : true,
			closeAction : "hide",
			items : [{
				xtype : "form",
				border : false,
				items :[{
					xtype : "combo",
					width : 385,
					fieldLabel : "انتخاب قالب نامه",
					colspan : 2,
					store: new Ext.data.Store({
						fields:["TemplateID","TemplateDesc"],
						proxy: {
							type: 'jsonp',
							url: '/loan/loan/loan.data.php?task=GetLetterTemplates',
							reader: {root: 'rows',totalProperty: 'totalCount'}
						}
					}),
					typeAhead: false,
					name : "TemplateID",
					valueField : "TemplateID",
					displayField : "TemplateDesc"
				}]
			}],
			buttons :[{
				text : "صدور نامه",
				iconCls : "save",
				itemId : "btn_save",
				handler : function(){ LoanLetterObject.RegisterLoanLetter();}
			},{
				text : "انصراف",
				iconCls : "undo",
				handler : function(){this.up('window').hide();}
			}]
		});
		Ext.getCmp(this.TabID).add(this.TemplateWin);
	}
	
	this.TemplateWin.show();

}

LoanLetter.prototype.RegisterLoanLetter = function(record){

	mask = new Ext.LoadMask(this.grid, {msg:'در حال ذخیره سازی ...'});
	mask.show();
	
	Ext.Ajax.request({
		url: this.address_prefix +'request.data.php',
		method: "POST",
		params : {
			task: "RegisterLoanLetter",
			RequestID: this.RequestID,
			TemplateID : this.TemplateWin.down("[name=TemplateID]").getValue()
		},
		
		success: function(response){
			mask.hide();
			var st = Ext.decode(response.responseText);

			if(st.success)
			{   
				LoanLetterObject.TemplateWin.hide();
				LoanLetterObject.grid.getStore().load();
			}
			else
			{
				if(st.data == "")
					Ext.MessageBox.alert("","عملیات مورد نظر با شکست مواجه شد");
				else
					Ext.MessageBox.alert("",st.data);
			}
		},
		failure: function(){}
	});
}

var LoanLetterObject = new LoanLetter();

</script>
<center>
	<div id="div_grid" style="width: 100%"></div>
</center>