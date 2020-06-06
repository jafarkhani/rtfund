<?php
//-----------------------------
//Programmer	: SH.Jafarkhani
//Date			: 94.06
//-----------------------------
require_once '../../header.inc.php';
require_once 'doc.class.php';


$docID = $_REQUEST["DocID"];
BeginReport();
?>
<center>
<?
if(isset($_REQUEST["v"]))
{
	switch($_REQUEST["v"])
	{
		case "admin":
			sys_config::$db_server['database'] = "sajakrrt_oldcomputes";
			PdoDataAccess::$DB = null;
			break;
		
		case "oldsaja":
			sys_config::$db_server['database'] = "sajakrrt_rtfund3";
			PdoDataAccess::$DB = null;
			break;
		
		case "saja":
			sys_config::$db_server['database'] = "sajakrrt_rtfund";
			PdoDataAccess::$DB = null;
			break;
	}
}
ACC_docs::PrintDoc($docID);
?>
</center>