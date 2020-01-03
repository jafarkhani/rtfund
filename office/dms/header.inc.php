<?php
//-----------------------------
//	Programmer	: SH.Jafarkhani
//	Date		: 94.06
//-----------------------------

require_once getenv("DOCUMENT_ROOT") . '/framework/configurations.inc.php';

set_include_path(get_include_path() . PATH_SEPARATOR . getenv("DOCUMENT_ROOT") . "/generalClasses");
set_include_path(get_include_path() . PATH_SEPARATOR . getenv("DOCUMENT_ROOT") . "/generalUI/ext4");

require_once getenv("DOCUMENT_ROOT") . '/definitions.inc.php';

require_once 'PDODataAccess.class.php';
require_once 'classconfig.inc.php';
require_once 'DataAudit.class.php';
require_once getenv("DOCUMENT_ROOT") . '/framework/management/framework.class.php';

require_once getenv("DOCUMENT_ROOT") . '/framework/session.php';
session::sec_session_start();
if(!session::checkLogin())
{
	echo "<script>window.location='/framework/login.php';</script>";
	die();
}

define("SYSTEMID", 7);

$address_prefix = getenv("DOCUMENT_ROOT");
$temp = preg_split('/\//', $_SERVER["SCRIPT_NAME"]);
$js_prefix_address = implode("/" , array_splice($temp,0,count($temp)-1)) . "/";
?>
