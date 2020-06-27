<?php
//---------------------------
// programmer:	Jafarkhani
// create Date:	94.06
//---------------------------
require_once '../../header.inc.php';

//................  GET ACCESS  .....................
$accessObj = FRW_access::GetAccess($_POST["MenuID"]);
//...................................................

$AddMode = isset($_REQUEST["mode"]) && $_REQUEST["mode"] == "adding" ? true : false;

require_once 'units.js.php';
?>
<div style="margin: 10" align="center">
	<div id="tree-div"></div>
	<div id="NewWIN" class="x-hide-display"></div>
</div>
