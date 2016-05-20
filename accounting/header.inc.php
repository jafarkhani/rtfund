<?php
//-----------------------------
//	Programmer	: SH.Jafarkhani
//	Date		: 94.06
//-----------------------------

require_once getenv("DOCUMENT_ROOT") . '/framework/configurations.inc.php';

set_include_path(get_include_path() . PATH_SEPARATOR . getenv("DOCUMENT_ROOT") . "/generalClasses");
set_include_path(get_include_path() . PATH_SEPARATOR . getenv("DOCUMENT_ROOT") . "/generalUI/ext4");

require_once 'PDODataAccess.class.php';
require_once 'classconfig.inc.php';
require_once 'DataAudit.class.php';

require_once getenv("DOCUMENT_ROOT") . '/accounting/definitions.inc.php';

require_once getenv("DOCUMENT_ROOT") . '/framework/session.php';
require_once getenv("DOCUMENT_ROOT") . '/framework/management/framework.class.php';

session::sec_session_start();
if(!session::checkLogin())
{
	if(isset($_REQUEST["portal"]))
		echo "<script>window.location='/portal/login.php';</script>";
	else
		echo "<script>window.location='/framework/login.php';</script>";
	die();
}

$address_prefix = getenv("DOCUMENT_ROOT");
$js_prefix_address = implode("/" , 
		array_splice(preg_split('/\//', $_SERVER["SCRIPT_NAME"]),0,
		count(preg_split('/\//', $_SERVER["SCRIPT_NAME"]))-1)) . "/";


?>
