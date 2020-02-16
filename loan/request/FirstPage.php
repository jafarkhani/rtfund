<?php
//-----------------------------
//	Programmer	: SH.Jafarkhani
//	Date		: 1394.07
//-----------------------------

require_once '../header.inc.php';
require_once inc_dataGrid;

$dg = new sadaf_datagrid("dg", $js_prefix_address . "request.data.php?task=SelectReceivedRequests", "grid_div");

$dg->addColumn("", "BorrowerDesc", "", true);

$col = $dg->addColumn("شماره وام", "RequestID", "");
$col->width = 90;

$col = $dg->addColumn("شعبه", "BranchName", "");
$col->width = 90;

$col = $dg->addColumn("تاریخ درخواست", "ReqDate", GridColumn::ColumnType_date);
$col->width = 110;

$col = $dg->addColumn("مبلغ درخواست", "ReqAmount", GridColumn::ColumnType_money);
$col->width = 100;

$col = $dg->addColumn("منبع", "ReqFullname");

$col = $dg->addColumn("گیرنده وام", "LoanFullname");
$col->renderer = "function(v,p,r){return v == '' || v == null ? r.data.BorrowerDesc : v;}";
$col->width = 120;

$dg->emptyTextOfHiddenColumns = true;
$dg->EnablePaging = false;
$dg->height = 150;
$dg->width = 770;
$dg->title = "درخواست های رسیده";
$dg->EnablePaging = false;
$dg->EnableSearch = false;
$dg->DefaultSortField = "ReqDate";
$dg->autoExpandColumn = "ReqFullname";
$grid_rec = $dg->makeGrid_returnObjects();

//---------------------------------------------
$dg = new sadaf_datagrid("dg", $js_prefix_address . "request.data.php?task=SelectReadyToPayParts", "grid_div");

$dg->addColumn("", "BorrowerDesc", "", true);

$col = $dg->addColumn("شماره وام", "RequestID", "");
$col->width = 70;
$col->align = "center";

$col = $dg->addColumn("شرایط وام", "PartDesc", "");
$col->width = 130;

$col = $dg->addColumn("تاریخ پرداخت", "PartDate", GridColumn::ColumnType_date);
$col->width = 100;

$col = $dg->addColumn("مبلغ ", "PartAmount", GridColumn::ColumnType_money);
$col->width = 100;

$col = $dg->addColumn("منبع", "ReqFullname");
$col->width = 190;

$col = $dg->addColumn("گیرنده وام", "LoanFullname");
$col->renderer = "function(v,p,r){return v == '' || v == null ? r.data.BorrowerDesc : v;}";

$dg->emptyTextOfHiddenColumns = true;
$dg->EnablePaging = false;
$dg->height = 150;
$dg->width = 770;
$dg->title = "درخواست های وام آماده پرداخت";
$dg->EnablePaging = false;
$dg->EnableSearch = false;
$dg->DefaultSortField = "PartDate";
$dg->autoExpandColumn = "LoanFullname";
$grid_pay = $dg->makeGrid_returnObjects();

//---------------------------------------------
$dg = new sadaf_datagrid("dg", $js_prefix_address . "request.data.php?task=SelectAllMessages&MsgStatus=RAW", "grid_div");

$dg->addColumn("", "BorrowerDesc", "", true);

$col = $dg->addColumn("شماره وام", "RequestID", "");
$col->width = 70;
$col->align = "center";

$col = $dg->addColumn("گیرنده وام", "LoanFullname");
$col->renderer = "function(v,p,r){return v == '' || v == null ? r.data.BorrowerDesc : v;}";
$col->width = 150;

$col = $dg->addColumn("فرستنده پیام", "RegPersonName", "");
$col->width = 150;

$col = $dg->addColumn("زمان ایجاد", "CreateDate", GridColumn::ColumnType_datetime);
$col->width = 120;

$col = $dg->addColumn("شرح", "details");
$col->ellipsis = 50;

$dg->emptyTextOfHiddenColumns = true;
$dg->EnablePaging = false;
$dg->height = 150;
$dg->width = 770;
$dg->title = "پیام های ارسالی از معرفی کنندگان وام";
$dg->EnablePaging = false;
$dg->EnableSearch = false;
$dg->DefaultSortField = "CreateDate";
$dg->autoExpandColumn = "details";
$grid_msg = $dg->makeGrid_returnObjects();

//------------------------------------------------------------------------------

require_once 'request.data.php';

$receivedCount = SelectReceivedRequests(true);
$_REQUEST["MsgStatus"] = "RAW";
$messagesCount = SelectAllMessages(true);
$readyToPayCount = SelectReadyToPayParts(true);
?>
<script>

LoanStartPage.prototype = {
	TabID : '<?= $_REQUEST["ExtTabID"]?>',
	address_prefix : "<?= $js_prefix_address?>",

	get : function(elementID){
		return findChild(this.TabID, elementID);
	}
};

function LoanStartPage(){
	
	this.grid_rec = <?= $grid_rec ?>;
	this.grid_rec.on("itemdblclick", function(view, record){
		framework.OpenPage("../loan/request/RequestInfo.php", "اطلاعات درخواست", 
		{RequestID : record.data.RequestID});
	});	
	//.......................................................
	this.grid_pay = <?= $grid_pay ?>;
	this.grid_pay.on("itemdblclick", function(view, record){
		framework.OpenPage("../loan/request/RequestInfo.php", "اطلاعات درخواست", 
		{RequestID : record.data.RequestID});
	});	
	//.......................................................
	this.grid_msg = <?= $grid_msg ?>;
	this.grid_msg.on("itemdblclick", function(view, record){
		framework.OpenPage("../loan/request/RequestInfo.php", "اطلاعات درخواست", 
		{RequestID : record.data.RequestID});
	});	
	
}

LoanStartPageObject = new LoanStartPage();

LoanStartPage.ShowGrid = function(gridName){
	
	eval("grid = LoanStartPageObject." + gridName + " ;");
	if(!grid.rendered)
		grid.render(LoanStartPageObject.get("div_" + gridName));
	else if(grid.isVisible())
		grid.hide();
	else
		grid.show();
}

</script>
<center><br>
	<div id="div_summary" align="right">
		<table id="div_content" align="right" style="width:85%;margin : 10 10 10 0">
			<tr>
				<td><img src="/generalUI/ext4/resources/themes/icons/comment.png" style="width:24px;vertical-align: middle;">
					پیام های ارسالی از معرفی کنندگان وام
					<a href="javascript:LoanStartPage.ShowGrid('grid_msg')">( <?= $messagesCount ?> )</a>
					<div id="div_grid_msg"></div>
				</td>
			</tr>
			<tr>
				<td><img src="/generalUI/ext4/resources/themes/icons/epay.png" style="width:24px;vertical-align: middle;">
					درخواست های وام آماده پرداخت
					<a href="javascript:LoanStartPage.ShowGrid('grid_pay')">( <?= $readyToPayCount ?> )</a>
					<div id="div_grid_pay"></div>
				</td>
			</tr>			
			<tr>
				<td><img src="/generalUI/ext4/resources/themes/icons/receive.png" style="width:24px;vertical-align: middle;">
					  درخواست های رسیده جدید
					<a href="javascript:LoanStartPage.ShowGrid('grid_rec')">( <?= $receivedCount ?> )</a>	
					<div id="div_grid_rec"></div>
				</td>
			</tr>
		</table>
	</div>
</center>