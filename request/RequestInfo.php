<?php
//-----------------------------
//	Programmer	: SH.Jafarkhani
//	Date		: 1394.06
//-----------------------------

require_once 'header.inc.php';
require_once inc_dataGrid;

if(session::IsPortal())
{
	$portal = true;
	$PersonID = $_SESSION["USER"]["PersonID"];
}
else
{
	$portal = false;
	$PersonID = $_POST["PersonID"];
}

if(empty($PersonID))
	die();

$justInfoTab = isset($_REQUEST["justInfoTab"]) ? true : false;

require_once 'RequestInfo.js.php';
?>
<!--<style>
	.PersonPicStyle {
		width : 150px;
		height: 150px;
		border: 1px solid black;
		border-radius: 50%;
	}
</style>-->
<br>
<center>
	<div id="test"></div>
<div><div id="mainForm"></div></div>
<?if(!$justInfoTab && session::IsPortal()){?>
<div>
تغییر مشخصات زیر فقط از طریق کارشناسان صندوق امکان پذیر می باشد:
<br>
اشخاص حقیقی : نام- نام خانوادگی  - کد ملی - شماره موبایل
<br>
اشخاص حقوقی : نام شرکت - شناسه اقتصادی - شماره پیامک
</div>
<?}?>
</center>