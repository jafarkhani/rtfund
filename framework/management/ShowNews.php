<?php
//---------------------------
// programmer:	Jafarkhani
// create Date: 96.12
//-----------------------

require_once '../../header.inc.php';
require_once 'framework.class.php';

$NewsID = (int)$_REQUEST["NewsID"];
$obj = new FRW_news($NewsID);

echo $obj->context;


?>
