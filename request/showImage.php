<?php
//---------------------------
// programmer:	Mahdipour
// create Date:	94.12
//---------------------------
require_once("header.inc.php");

$object = "LetterPic";
/*var_dump($_REQUEST);*/
$Image = PdoDataAccess::runquery("select $object from request where IDReq=?",array($_GET["IDReq"]));

if($Image[0][0] == "")
{
	header('Content-type: image/png');
	header('Pragma: no-cache');
	header('Expires: 0');
	header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
	header('Pragma: public');
	header("Content-Transfer-Encoding: binary");

	echo file_get_contents( getenv("DOCUMENT_ROOT") . "/framework/icons/NoPic.png");
	die();
}

header('Content-type: image');
header('Pragma: no-cache');
header('Expires: 0');
header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
header('Pragma: public');
header("Content-Transfer-Encoding: binary");

echo $Image[0][0] ;
die();

?>