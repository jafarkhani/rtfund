<?php
//-----------------------------
//	Programmer	: SH.Jafarkhani
//	Date		: 1394.12
//-----------------------------

require_once '../header.inc.php';
require_once inc_dataGrid;

//................  GET ACCESS  .....................
$accessObj = FRW_access::GetAccess($_POST["MenuID"]);
//...................................................

if(!isset($_REQUEST["FormType"]))
    die();
$FormType = $_REQUEST["FormType"];

require_once 'ManagePlans.js.php';

$portal = session::IsPortal() ? true : false;
$expert = isset($_REQUEST["expert"]) ? true : false;

$dg = new sadaf_datagrid("dg", $js_prefix_address . "plan.data.php?task=SelectAllPlans&FormType=" . $FormType .
    ($expert ? "&expert=true" : ""), "grid_div");

$dg->addColumn("", "StepID", "", true);

$col = $dg->addColumn("شماره", "PlanID", "");
$col->width = 30;

$col = $dg->addColumn("عنوان طرح", "PlanDesc", "");
$col->width = 190;

$col = $dg->addColumn("تاریخ درخواست", "LetterDate", GridColumn::ColumnType_date);
$col->width = 90;

$col = $dg->addColumn("درخواست کننده", "ReqFullname");
$col->width = 100;

$col = $dg->addColumn(" شماره نامه", "LetterID");
$col->renderer = "ManagePlan.ParamValueRender";
$col->width = 60;

$col = $dg->addColumn("متقاضی ارزیابی", "askername");
$col->width = 100;

$col = $dg->addColumn("نوع ارزیابی", "InfoDesc" );
/*$col->renderer = "function(v,p,r){return v == 1 ? 'تسهیلات' : v == 2 ? 'مشارکت مدنی' : v == 3 ? 'مشارکت حقوقی' : v == 4 ? 'صدور ضمانتنامه' : v == 5 ? 'سایر' : 'ندارد' ;}";*/
$col->width = 100;

$col = $dg->addColumn("مبلغ تسهیلات", "FacilityAmount");
$col->width = 100;

$col = $dg->addColumn("وضعیت", "StepDesc", "");
$col->width = 100;

if(!$portal && !$expert)
{
    $dg->addObject('ManagePlanObject.AllPlansObj');

    /*if($accessObj->RemoveFlag)
    {
        $col = $dg->addColumn('حذف', '', 'string');
        $col->renderer = "ManagePlan.DeleteRender";
        $col->width = 40;
        $col->align = "center";
    }*/
}
else if($portal)
{
    $col = $dg->addColumn('طرح', '', 'string');
    $col->renderer = "ManagePlan.PlanInfoRender";
    $col->width = 40;
    $col->align = "center";
}

$col = $dg->addColumn('سابقه', '', 'string');
$col->renderer = "ManagePlan.HistoryRender";
$col->width = 40;
$col->align = "center";

$dg->emptyTextOfHiddenColumns = true;
$dg->height = 450;
$dg->pageSize = 15;
$dg->width = 920;
$dg->title = "طرح های ارسالی";
$dg->DefaultSortField = "RegDate";
$dg->autoExpandColumn = "PlanDesc";
$grid = $dg->makeGrid_returnObjects();
?>
<script>
    ManagePlanObject.grid = <?= $grid ?>;
    <? if(!$portal){ ?>
    ManagePlanObject.grid.on("itemdblclick", function(view, record){
        framework.OpenPage("/plan/plan/PlanInfo.php", "جداول اطلاعاتی طرح", {
            PlanID : record.data.PlanID,
            MenuID : ManagePlanObject.MenuID
        });
    });
    <? } ?>
    ManagePlanObject.grid.getView().getRowClass = function(record, index)
    {
        if(record.data.StepID == "<?= STEPID_REJECT ?>")
            return "pinkRow";

        return "";
    }

    ManagePlanObject.grid.render(ManagePlanObject.get("DivGrid"));
</script>
<center><br>
    <div id="DivGrid"></div>
    <br>
    <div id="LoanInfo"></div>
</center>