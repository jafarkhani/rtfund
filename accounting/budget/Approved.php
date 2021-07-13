<?php
//-------------------------
// programmer:	Jafarkhani
// Create Date:	98..03
//-------------------------
require_once('../../header.inc.php');
require_once getenv("DOCUMENT_ROOT") . '/accounting/baseinfo/baseinfo.class.php';
require_once inc_dataGrid;

//................  GET ACCESS  .....................
$accessObj = FRW_access::GetAccess($_POST["MenuID"]);
//...................................................

$dg = new sadaf_datagrid("dg", $js_prefix_address . "budget.data.php?task=SelectBudgetApproved", "grid_div");

$dg->addColumn("", "ApprovedID", "", true);
$dg->addColumn("", "BudgetID", "", true);
$dg->addColumn("", "BudgetDesc", "", true);
$dg->addColumn("", "CycleYear", "", true);
$dg->addColumn("", "CycleID", "", true);

$col = $dg->addColumn("دوره مالی", "CycleYear");
$col->width = 70;
$col = $dg->addColumn("عنوان بودجه", "BudgetDesc");
$col->width = 400;
/*$col = $dg->addColumn("تاریخ تخصیص", "AllocDate", GridColumn::ColumnType_date);
$col->width = 150;*/
$col = $dg->addColumn("مبلغ مصوب", "approvedAmount", GridColumn::ColumnType_money);
$col->width = 150;
$col = $dg->addColumn("مبلغ پیش بینی", "PrevisionAmount", GridColumn::ColumnType_money);
$col->width = 150;
/*$dg->addColumn("جزئیات", "details", "");*/
//$dg->EnableGrouping = true;
//$dg->DefaultGroupField = "BudgetDesc";

if($accessObj->AddFlag)
{
	$dg->addButton = true;
	$dg->addHandler = "function(){ACC_BudgetApprovedObj.AddBudgetApproved();}";
}
if($accessObj->RemoveFlag)
{
	$col = $dg->addColumn("عملیات", "");
	$col->sortable = false;
	$col->renderer = "function(v,p,r){return ACC_BudgetApproved.OperationRender(v,p,r);}";
	$col->width = 50;
}

$dg->title = "بودجه مصوب";
$dg->height = 500;
$dg->width = 820;
$dg->DefaultSortField = "ApprovedID";
/*$dg->autoExpandColumn = "details";*/
$dg->emptyTextOfHiddenColumns = true;
$grid = $dg->makeGrid_returnObjects();

?>
<center>
    <form id="mainForm">
		<div id="newDiv"></div>
        <div id="grid_div" style="margin: 10px"></div>
    </form>
</center>
<script>

    ACC_BudgetApproved.prototype = {
	TabID : '<?= $_REQUEST["ExtTabID"] ?>',
	address_prefix : '<?= $js_prefix_address ?>',

	AddAccess : <?= $accessObj->AddFlag ? "true" : "false" ?>,
	EditAccess : <?= $accessObj->EditFlag ? "true" : "false" ?>,
	RemoveAccess : <?= $accessObj->RemoveFlag ? "true" : "false" ?>,

	get : function(elementID){
		return findChild(this.TabID, elementID);
	}
}

function ACC_BudgetApproved(){

	this.grid = <?= $grid ?>;
	this.grid.render(this.get("grid_div"));
	
	this.formPanel = new Ext.form.Panel({
		frame : true,
		hidden : true,
		style : "margin:10px 0 10px",
		renderTo : this.get("newDiv"),
		width : 700,
		layout : {
			type : "table",
			columns : 2
		},
		defaults : {width : 300},
		title : "بودجه مصوب",
		items : [{
			xtype : "treecombo",
			selectChildren: true,
			canSelectFolders: false,
			multiselect : false,
			name : "BudgetID",
			colspan : 2,
			width : 620,
			fieldLabel: "بودجه",
			store : new Ext.data.TreeStore({
				proxy: {
					type: 'ajax',
					url:  this.address_prefix + 'budget.data.php?task=GetBudgetTree' 
				},
				root: {
					text: "بودجه",
					id: 'src',
					expanded: true
				}
			})
		},{
                xtype : "combo",
                colspan : 2,
                width : 400,
                store : new Ext.data.SimpleStore({
                    proxy: {
                        type: 'jsonp',
                        url: "/accounting/global/domain.data.php?task=SelectCycles",
                        reader: {root: 'rows',totalProperty: 'totalCount'}
                    },
                    fields : ['CycleID','CycleDesc'],
                    autoLoad : true
                }),
                fieldLabel : "دوره",
                /*queryMode : 'local',*/
                /*value : "<?= !isset($_SESSION["accounting"]["CycleID"]) ? "" : $_SESSION["accounting"]["CycleID"] ?>",*/
                displayField : "CycleDesc",
                valueField : "CycleID",
                name : "CycleID",
                hiddenName : "CycleID"
            },{
			xtype : "currencyfield",
			fieldLabel : "مبلغ مصوب",
			name : "approvedAmount",
			hideTrigger : true
		},{
            xtype : "currencyfield",
            fieldLabel : "مبلغ پیش بینی",
            name : "PrevisionAmount",
            hideTrigger : true
		},{
			xtype : "hidden",
			name : "ApprovedID"
		}],
		buttons : [{
			text : "ذخیره",
			iconCls : "save",
			handler : function(){ACC_BudgetApprovedObj.SaveBudgetApproved();}
		},{
			text : "انصراف",
			iconCls : "undo",
			handler : function(){this.up('panel').hide();}
		}]
	});
}

    ACC_BudgetApproved.OperationRender = function(v,p,r){
	
	st = "";
	
	if(ACC_BudgetApprovedObj.EditAccess)
		st += "<div align='center' title='ویرایش' class='edit' "+
		"onclick='ACC_BudgetApprovedObj.EditBudgetApproved();' " +
		"style='float:right;background-repeat:no-repeat;background-position:center;" +
		"cursor:pointer;width:50%;height:16'></div>";
	if(ACC_BudgetApprovedObj.RemoveAccess)
		st += "<div align='center' title='حذف' class='remove' "+
		"onclick='ACC_BudgetApprovedObj.DeleteBudgetApproved();' " +
		"style='float:right;background-repeat:no-repeat;background-position:center;" +
		"cursor:pointer;width:50%;height:16'></div>";
		
	return st;
}

    ACC_BudgetApproved.prototype.AddBudgetApproved = function(){

	this.formPanel.getForm().reset();
	this.formPanel.show();
}

    ACC_BudgetApproved.prototype.EditBudgetApproved = function(){

	var record = this.grid.getSelectionModel().getLastSelected();
	this.formPanel.loadRecord(record);
	this.formPanel.show();
}

    ACC_BudgetApproved.prototype.SaveBudgetApproved = function(){

	mask = new Ext.LoadMask(this.formPanel,{msg:'در حال ذخیره سازی ...'});
	mask.show();

	this.formPanel.getForm().submit({
		url: this.address_prefix +'budget.data.php',
		method: "POST",
		params: {
			task: "SaveBudgetApproved"
		},
		success: function(form,result){
			mask.hide();
            ACC_BudgetApprovedObj.grid.getStore().load();
            ACC_BudgetApprovedObj.formPanel.hide();
		},
		failure: function(form,action){
			mask.hide();
			Ext.MessageBox.alert("ERROR", action.result.data == "" ? "عملیات مورد نظر با شکست مواجه گردید" : action.result.data);
		}
	});
}

    ACC_BudgetApproved.prototype.DeleteBudgetApproved = function(){
	
	Ext.MessageBox.confirm("","آیا مایل به حذف می باشید؟", function(btn){
		if(btn == "no")
			return;
		
		me = ACC_BudgetApprovedObj;
		var record = me.grid.getSelectionModel().getLastSelected();
		
		mask = new Ext.LoadMask(Ext.getCmp(me.TabID), {msg:'در حال حذف ...'});
		mask.show();

		Ext.Ajax.request({
			url: me.address_prefix + 'budget.data.php',
			params:{
				task : "DeleteBudgetApproved",
                ApprovedID : record.data.ApprovedID
			},
			method: 'POST',

			success: function(response,option){
				mask.hide();
				sd = Ext.decode(response.responseText);
				if(sd.success)
				{
                    ACC_BudgetApprovedObj.grid.getStore().load();
				}	
				else
				{
					Ext.MessageBox.alert("ERROR", sd.data == "" ? "عملیات مورد نظر با شکست مواجه گردید" : sd.data);
				}
			},
			failure: function(){}
		});
	});
}

var ACC_BudgetApprovedObj = new ACC_BudgetApproved();

</script>