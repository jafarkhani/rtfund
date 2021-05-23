<?php
//---------------------------
// programmer:	Mahdipour
// create Date:	400.02
//---------------------------
ini_set("display_errors", "on");
require_once '../../header.inc.php';
require_once inc_dataGrid;
require_once inc_dataReader;

require_once 'ManageWarrentyReq.js.php';

$dg = new sadaf_datagrid("WGrid", $js_prefix_address . "ManageWarrentyReq.data.php?task=searchWR", "WDIV");

$dg->addColumn(" شناسه رکورد", "BID", "int",true);
$dg->addColumn("کد پرسنلی", "PersonID", "int",true);
$dg->addColumn(" ", "BailType", "int",true); 
$dg->addColumn(" ", "param1", "int",true); 
$dg->addColumn(" ", "param2", "int",true); 
$dg->addColumn(" ", "param3", "int",true); 
$dg->addColumn(" ", "param4", "int",true); 
$dg->addColumn(" ", "param5", "int",true); 
$dg->addColumn(" ", "LetterNo", "int",true); 

$col = $dg->addColumn("نام شخص حقیقی/حقوقی", "fullname", "");
$col->width = 150;

$col = $dg->addColumn("نوع ضمانت نامه ", "BailTypeTitle","string");
$col->width = 100;

$col = $dg->addColumn("موضوع قرارداد", "subject","string");
$col->width = 250;

$col = $dg->addColumn("وضعیت ", "status","string");
$col->width = 80;

$col = $dg->addColumn("عملیات", "", "string");
$col->renderer = "function(v,p,r){return WelfareCenters.opRender(v,p,r);}";
$col->width = 80;

$dg->addButton = true;
$dg->addHandler = "function(){WelfareCentersObject.AddWFC();}";

$dg->pageSize = "20";
$dg->EnableSearch = false ;
$dg->emptyTextOfHiddenColumns = true;
$dg->width = 660;
$dg->height = 430;
$dg->title = "لیست ضمانت نامه ها";
$dg->autoExpandColumn = "title";

$grid = $dg->makeGrid_returnObjects();

?>
<style>.suite {background-image:url('/fumservices/HTL/img/suite.png') !important;}</style>

<script>
    WelfareCentersObject.grid = <?=$grid?>;  
    WelfareCentersObject.grid.render("WDIV");
    
</script>
<center>
    <div id="ErrorDiv" style="width:40%"></div><br>
    <form id="mainForm"> <div id="mainpanel"></div> </form>    <br>
    <div id="WDIV" style="width:100%"></div>
</center>
