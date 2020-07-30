<?php
//-------------------------
// programmer:	Jafarkhani
// Create Date:	98.10
//-------------------------
require_once('../../header.inc.php');
require_once inc_dataGrid;
 
//................  GET ACCESS  .....................
$accessObj = FRW_access::GetAccess(1145);
//...................................................

$RequestID = isset($_REQUEST["RequestID"]) ? $_REQUEST["RequestID"] : 0;

$dg = new sadaf_datagrid("dg",$js_prefix_address . "request.data.php?task=GetFollowsToDo&RequestID=" .$RequestID,"grid_div");

$dg->addColumn("", "ToDoStatusID","", true);

$col = $dg->addColumn("شماره وام", "RequestID");
$col->renderer = "LoanFollowToDo.LoanRender";
$col->width = 80;

$col = $dg->addColumn("مشتری", "LoanPersonName");

$col = $dg->addColumn("مانده کل", "totalAmount", GridColumn::ColumnType_money);
$col->width = 100;

$col = $dg->addColumn("مانده معوق", "CurrentRemain", GridColumn::ColumnType_money);
$col->width = 100;

$col = $dg->addColumn("معرف", "ReqPersonName");
$col->width = 180;

$col = $dg->addColumn("مرحله انجام شده", "CurrentStatusDesc");
$col->width = 130;

$col = $dg->addColumn("تاریخ اقدام", "RegDate", GridColumn::ColumnType_date);
$col->width = 120;

$col = $dg->addColumn("تعداد روز گذشته", "DiffDays");
$col->width = 60;

$col = $dg->addColumn("مرحله رسیده", "ToDoDesc");
$col->width = 130;

$col = $dg->addColumn('عملیات', '', 'string');
$col->renderer = "LoanFollowToDo.OperationRender";
$col->width = 50;
$col->align = "center";

$dg->title = "پیگیری های رسیده";
$dg->height = 500;
$dg->emptyTextOfHiddenColumns = true;
$dg->DefaultSortField = "StatusID";
$dg->DefaultSortDir = "DESC";
$dg->EnablePaging = false;
$dg->EnableSearch = false;
$dg->HeaderMenu = false;
$dg->autoExpandColumn = "LoanPersonName";

$grid = $dg->makeGrid_returnObjects();

?>
<script type="text/javascript">

LoanFollowToDo.prototype = {
	TabID : '<?= $_REQUEST["ExtTabID"]?>',
	address_prefix : "<?= $js_prefix_address?>",
	
	AddAccess : <?= $accessObj->AddFlag ? "true" : "false" ?>,
	EditAccess : <?= $accessObj->EditFlag ? "true" : "false" ?>,
	RemoveAccess : <?= $accessObj->RemoveFlag ? "true" : "false" ?>,

	RequestID : <?= $RequestID ?>,
	
	get : function(elementID){
		return findChild(this.TabID, elementID);
	}
};

function LoanFollowToDo(){
	
	this.grid = <?= $grid ?>;
	this.grid.render(this.get("div_grid"));	
}

LoanFollowToDo.OperationRender = function(value, p, record){
	
	return "<div  title='عملیات' class='setting' onclick='LoanFollowToDoObject.OperationMenu(event);' " +
		"style='background-repeat:no-repeat;background-position:center;" +
		"cursor:pointer;width:100%;height:16'></div>";
}

LoanFollowToDo.LoanRender = function(value, p, record){
	
	return "<a href='javascript:void(0)' onclick=LoanFollowToDo.OpenLoan("+value+")>" + value + "<a>";
}

LoanFollowToDo.OpenLoan = function(RequestID){
	framework.OpenPage("../loan/request/RequestInfo.php", "اطلاعات درخواست", 
		{RequestID : RequestID});
}

LoanFollowToDo.OpenLetter = function(LetterID){
	
	framework.OpenPage("/office/letter/LetterInfo.php", "مشخصات نامه", 
	{
		LetterID : LetterID
	});
}

LoanFollowToDo.prototype.OperationMenu = function(e){

	record = this.grid.getSelectionModel().getLastSelected();
	var op_menu = new Ext.menu.Menu();
	
	op_menu.add({text: 'ثبت انجام پیگیری',iconCls: 'tick', 
		handler : function(){ LoanFollowToDoObject.DoFollow();	}});
	
	op_menu.add({text: 'گزارش پرداخت',iconCls: 'report', 
		handler : function(){ 
			me = LoanFollowToDoObject;
			var record = me.grid.getSelectionModel().getLastSelected();
			window.open(me.address_prefix + "../report/DebitReport.php?show=true&RequestID=" + 
					record.data.RequestID);
	}});

	op_menu.add({text: 'گزارش طبقه بدهی',iconCls: 'report', 
		handler : function(){ 
			me = LoanFollowToDoObject;
			var record = me.grid.getSelectionModel().getLastSelected();
			window.open(me.address_prefix + "../report/DebitClassification.php?show=true&fromRequestID=" + 
					record.data.RequestID + "&toRequestID=" + record.data.RequestID);
	}});

	op_menu.showAt(e.pageX-120, e.pageY);
}

LoanFollowToDo.prototype.DoFollow = function(record){

	Ext.MessageBox.confirm("","آیا پیگیری مورد نظر را انجام داده اید؟", function(btn){
		if(btn == "no")
			return;
		
		me = LoanFollowToDoObject;
		
		mask = new Ext.LoadMask(me.grid, {msg:'در حال ذخیره سازی ...'});
		mask.show();
		
		var record = me.grid.getSelectionModel().getLastSelected();
		Ext.Ajax.request({
			url: me.address_prefix + 'request.data.php',
			params:{
				task: "DoFollow",
				RequestID : record.data.RequestID,
				ToDoStatusID : record.data.ToDoStatusID
			},
			method: 'POST',

			success: function(response,option){
				mask.hide();
				result = Ext.decode(response.responseText);
				if(result.success)
				{
					if(result.data != "")
					{
						LoanFollowToDoObject.grid.getStore().load({
							callback : function(){LoanFollowToDo.OpenLetter(result.data);}
						});
					}
					else
						LoanFollowToDoObject.grid.getStore().load();
				}
				else if(result.data == "")
					Ext.MessageBox.alert("","عملیات مورد نظر با شکست مواجه شد");
				else
					Ext.MessageBox.alert("",result.data);
			},
			failure: function(){}
		});
	});
}

var LoanFollowToDoObject = new LoanFollowToDo();

</script>
<center>
	<br>
	<div id="div_grid" style="width:98%"></div>
</center>