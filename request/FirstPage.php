<?php
//-----------------------------
//	Programmer	: SH.Jafarkhani
//	Date		: 1394.07
//-----------------------------

/*require_once '../../header.inc.php';
require_once inc_dataGrid;*/
require_once 'header.inc.php';
require_once inc_dataGrid;
require_once 'Request.class.php';

$dg = new sadaf_datagrid("dg", $js_prefix_address . "Request.data.php?task=SelectReceivedRequests", "grid_div");
$col=$dg->addColumn("RID","IDReq","string");
$col->width = 40;
$col = $dg->addColumn("درخواست کننده","fullname","string");
$col->width = 100;
$dg->addColumn("تاریخ مراجعه","referalDate", GridColumn::ColumnType_date);
$dg->addColumn("ساعت مراجعه","referalTime","string");
$dg->addColumn("نوع خدمت","serviceType","string");
$col=$dg->addColumn("شرح خدمت","otherService","string");
$col->width = 320;

$dg->emptyTextOfHiddenColumns = true;
$dg->EnablePaging = false;
$dg->height = 150;
$dg->width = 770;
$dg->title = "درخواست های رسیده";
$dg->EnablePaging = false;
$dg->EnableSearch = false;
$dg->DefaultSortField = "IDReq";
$dg->autoExpandColumn = "fullname";
$grid_req = $dg->makeGrid_returnObjects();

//------------------------------------------------------------------------------

require_once 'Request.data.php';

$receivedCount = SelectReceivedRequests(true);
$_REQUEST["MsgStatus"] = "RAW";

?>
<script>

ReqStartPage.prototype = {
	TabID : '<?= $_REQUEST["ExtTabID"]?>',
	address_prefix : "<?= $js_prefix_address?>",

	get : function(elementID){
		return findChild(this.TabID, elementID);
	}
};

function ReqStartPage(){
	
	this.grid_req = <?= $grid_req ?>;
	this.grid_req.on("itemdblclick", function(view, record){
		framework.OpenPage("../request/RequestInfo.php", "اطلاعات درخواست", 
		{PersonID : record.data.IDReq});
	});	
	//.......................................................
	
}

ReqStartPageObject = new ReqStartPage();

ReqStartPage.ShowGrid = function(gridName){
	
	eval("grid = ReqStartPageObject." + gridName + " ;");
	if(!grid.rendered)
		grid.render(ReqStartPageObject.get("div_" + gridName));
	else if(grid.isVisible())
		grid.hide();
	else
		grid.show();
}

</script>
<center><br>
	<div id="div_summary_req" align="right">
		<table id="div_content_req" align="right" style="width:85%;margin : 0 10 0 0">
			<tr>
				<td><img src="/generalUI/ext4/resources/themes/icons/receive.png" style="width:24px;vertical-align: middle;">
					  درخواست های رسیده جدید
					<a href="javascript:ReqStartPage.ShowGrid('grid_req')">( <?= $receivedCount ?> )</a>	
					<div id="div_grid_req"></div>
				</td>
			</tr>
		</table>
	</div>
</center>