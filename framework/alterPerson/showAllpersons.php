<?php
//-----------------------------
//	Programmer	: SH.Jafarkhani
//	Date		: 94.06
//-----------------------------
require_once("conf/header.inc.php");
require_once inc_dataGrid;
require_once 'showAllpersons.js.php';

//................  GET ACCESS  .....................
$accessObj = FRW_access::GetAccess($_POST["MenuID"]);
//...................................................

$dg = new sadaf_datagrid("dg",$js_prefix_address . "alterPersons.data.php?task=selectAlterPersons", "div_grid_person");

$col = $dg->addColumn("PID","AlterPersonID","string");
$col->width = 25;

$dg->addColumn("نام و نام خانوادگی","fullname","string");
$col = $dg->addColumn("تاریخ تکمیل درخواست", "fillDate", GridColumn::ColumnType_date);
$col->width = 90;
$col = $dg->addColumn("تاریخ تولد ", "BirthDate", GridColumn::ColumnType_date);
$col->width = 90;

$col=$dg->addColumn("وضعیت تاهل","marital","string");
$col->renderer ="function(v){return (v=='Married') ? 'متاهل' : (v=='Single') ? 'مجرد' : '' ;}";
$col->width = 75;

$dg->addColumn("آخرین مدرک تحصیلی","educationDeg","string");
$col = $dg->addColumn("سابقه کاری(سال)","WorkExp","string");
$col->width = 50;
$dg->addColumn("حوزه کاری","assistPart","string");
$dg->addColumn("حقوق درخواستی(تومان)","reqWage","string");
$dg->addColumn("تلفن همراه","mobile","string");


/*$dg->addColumn("کد ملی","NationalID","string");
$col=$dg->addColumn("جنسیت","sex","string");
$col->renderer ="function(v){return (v=='MALE') ? 'مرد' : (v=='WOMAN') ? 'زن' : '' ;}";*/

if($accessObj->RemoveFlag)
{
    $col = $dg->addColumn("حذف","AlterPersonID","");
    $col->renderer = "Person.deleteRender";
    $col->sortable = false;
    $col->width = 40;
}

$dg->addObject("PersonObject.FilterObj");


$dg->height = 500;
$dg->pageSize = 15;
$dg->width = 900;
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
    var PersonObject = new Person();

    PersonObject.grid = <?= $grid?>;
    PersonObject.grid.getView().getRowClass = function(record)
    {
        return "";
    }
    PersonObject.grid.on("itemdblclick", function(view, record){

        framework.OpenPage("/framework/alterPerson/AlterPersonInfo.php", "اطلاعات افراد جانشین",
            {
                PersonID : record.data.AlterPersonID
            });

    });
    PersonObject.grid.render(PersonObject.get("div_grid_user"));
</script>
<center>
	<div id="div_info"></div>
	<br>
	<div id="div_grid_user"></div>
	<br>
	<div id="info"></div>
</center>