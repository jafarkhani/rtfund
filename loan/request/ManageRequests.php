<?php
//-----------------------------
//	Programmer	: SH.Jafarkhani
//	Date		: 1394.07
//-----------------------------

require_once '../../header.inc.php';
require_once inc_dataGrid;

//................  GET ACCESS  .....................
$accessObj = FRW_access::GetAccess($_POST["MenuID"]);
//...................................................
$accountMode = !empty($_REQUEST["accounting"]) ? true : false;

$dg = new sadaf_datagrid("dg", $js_prefix_address . "request.data.php?task=SelectAllRequests", "grid_div");

$dg->addColumn("", "StatusID", "", true);
$dg->addColumn("", "BranchID", "", true);
$dg->addColumn("", "BorrowerDesc", "", true);
$dg->addColumn("", "BorrowerID", "", true);
$dg->addColumn("", "BorrowerMobile", "", true);
$dg->addColumn("", "LoanPersonID", "", true);
$dg->addColumn("", "ReqPersonID", "", true);
$dg->addColumn("", "IsEnded", "", true);
$dg->addColumn("", "ContractDocID", "", true);
$dg->addColumn("", "AllocDocID", "", true);
$dg->addColumn("","IsConfirm","string", true);

$col = $dg->addColumn("گیرنده وام", "LoanFullname");
$col->renderer = "function(v,p,r){return v == '' || v == null ? '<span style=color:red>' + r.data.BorrowerDesc + '</span>' : v;}";

$col = $dg->addColumn("<span style=font-size:8px>شناسه پرداخت</span>", "RequestID", "");
$col->renderer = "LoanRFID";
$col->width = 70;

$col = $dg->addColumn("شماره", "RequestID", "");
$col->width = 50;

$col = $dg->addColumn("شعبه", "BranchName", "");
$col->width = 100;

$col = $dg->addColumn("تاریخ", "ReqDate", GridColumn::ColumnType_date);
$col->width = 80;

$col = $dg->addColumn("مبلغ درخواست", "ReqAmount", GridColumn::ColumnType_money);
$col->width = 100;

$col = $dg->addColumn("نوع وام", "LoanDesc");
$col->width = 100;

$col = $dg->addColumn("منبع", "ReqFullname");
$col->width = 100;

$col = $dg->addColumn("وضعیت", "StatusDesc", "");
$col->width = 100;

if($accountMode)
{
    $col = $dg->addColumn("سند عقد قرارداد", "ContractLocalNo", "");
    $col->renderer = "ManageRequest.OpenContractDoc";
    $col->width = 60;

    $col = $dg->addColumn("سند تخصیص", "AllocLocalNo", "");
    $col->renderer = "ManageRequest.OpenAllocDoc";
    $col->width = 60;
}
$col = $dg->addColumn('عملیات', '', 'string');
$col->renderer = "ManageRequest.OperationRender";
$col->width = 50;
$col->align = "center";

$dg->addObject("this.FilterObj");

if($accessObj->EditFlag)
	$dg->addButton("", "تایید", "tick", "function(){ManageRequestObject.Confirm();}");

$dg->emptyTextOfHiddenColumns = true;
$dg->height = 500;
$dg->title = "درخواست های وام";
$dg->DefaultSortField = "ReqDate";
$dg->autoExpandColumn = "LoanFullname";
$grid = $dg->makeGrid_returnObjects();

//----------------------------------------------

require_once "request.class.php";
$temp = LON_ReqParts::GetRejectParts();
$rejectedParts = common_component::PHPArray_to_JSSimpleArray($temp);

//----------------------------------------------

require_once 'ManageRequests.js.php';
?>
<center><br>
	<div id="rejectedDIV"></div>
	
	<form id="MainForm">
		<div id="div_form"></div>
	</form>
	<br>
	<div id="DivGrid" style="width:98%"></div>	
	ردیف های بنفش رنگ وام های تایید شده هستند
	<br>
	ردیف های سبز رنگ وام های خاتمه یافته می باشند
	<br>
	ردیف های قرمز رنگ وام های عدم تایید می باشند
</center>