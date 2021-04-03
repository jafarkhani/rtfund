<?php
//-----------------------------
//	Programmer	: SH.Jafarkhani
//	Date		: 94.06
//-----------------------------
require_once("header.inc.php");
require_once inc_dataGrid;
require_once 'RequestManage.js.php';

//................  GET ACCESS  .....................
$accessObj = FRW_access::GetAccess($_POST["MenuID"]);
//...................................................

$dg = new sadaf_datagrid("dg",$js_prefix_address . "Request.data.php?task=selectAllRequest", "div_grid_person");

$dg->addColumn("", "IsRegister", "", true);
$dg->addColumn("", "IsPresent", "", true);
$dg->addColumn("", "IsInfoORService", "", true);
$dg->addColumn("", "IsRelated", "", true);
$dg->addColumn("", "habitRange", "", true);
$dg->addColumn("", "result", "", true);

$col=$dg->addColumn("RID","IDReq","string");
$col->width = 20;
$dg->addColumn("نام ونام خانوادگی ذینفع","fullname","string");
$dg->addColumn("نام ونام خانوادگی متقاضی","askerName","string");
$dg->addColumn("تاریخ مراجعه","referalDate", GridColumn::ColumnType_date);
$dg->addColumn("ساعت مراجعه","referalTime","string");

$col = $dg->addColumn(" شماره نامه", "LetterID", "");
$col->renderer = "Request.ParamValueRender";
$col->width = 70;

$dg->addColumn("نوع خدمت","serviceType","string");
/*$dg->addColumn("شرح خدمت","otherService","string");*/
/*$dg->addColumn("شرح اطلاعات","InformationDesc","string");*/
$dg->addColumn("کارشناس ارجاعی","refername","string");
/*$dg->addColumn("کارشناس ارجاعی","referPersonID","string");*/
/*$dg->addColumn("شرح ارجاعی","referDesc","string");*/
$col = $dg->addColumn("نظرسنجی","Poll","string");
$col->renderer ="function(v){return (v=='1') ? 'ضعیف' : (v=='2') ? 'متوسط' : (v=='3') ? 'خوب' : (v=='4') ? 'عالی' : '' ;}";
/*$col=$dg->addColumn("جنسیت","sex","string");
$col->renderer ="function(v){return (v=='MALE') ? 'مرد' : (v=='FEMALE') ? 'زن' : '' ;}";*/

$col = $dg->addColumn('عملیات', '', 'string');
$col->renderer = "Request.OperationRender";
$col->width = 50;
$col->align = "center";

/*if($accessObj->RemoveFlag)
{
    $col = $dg->addColumn("حذف","AlterPersonID","");
    $col->renderer = "Request.deleteRender";
    $col->sortable = false;
    $col->width = 40;
}*/

$dg->addObject("RequestObject.FilterObj");

$dg->EnableSearch = true;
$dg->height = 500;
$dg->pageSize = 15;
$dg->width = 880;
/*$dg->DefaultSortField = "PID";
$dg->DefaultSortDir = "ASC";
$dg->autoExpandColumn = "PID";*/
$dg->emptyTextOfHiddenColumns = true;

$grid = $dg->makeGrid_returnObjects();

?>
<style type="text/css">
.pinkRow, .pinkRow td,.pinkRow div{ background-color:#FFB8C9 !important;}
</style>

<script>
    var RequestObject = new Request();

    RequestObject.grid = <?= $grid?>;
    RequestObject.grid.getView().getRowClass = function(record)
    {
        return "";
    }
    RequestObject.grid.on("itemdblclick", function(view, record){

        framework.OpenPage("/request/RequestInfo.php", "اطلاعات درخواست ها",
            {
                PersonID : record.data.IDReq
            });

    });
    RequestObject.grid.render(RequestObject.get("div_grid_user"));
</script>
<center>
	<div id="div_info"></div>
	<br>
	<div id="div_grid_user"></div>
	<br>
	<div id="info"></div>
</center>