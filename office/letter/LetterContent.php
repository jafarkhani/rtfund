<?php
//-----------------------------
//	Programmer	: SH.Jafarkhani
//	Date		: 94.10
//-----------------------------
require_once '../header.inc.php';
require_once 'letter.class.php';
require_once inc_dataReader;
require_once '../dms/dms.class.php';

$LetterID = !empty($_GET["LetterID"]) ? $_GET["LetterID"] : "";
if(empty($LetterID))
	die();

$LetterObj = new OFC_letters($LetterID);
echo $LetterObj->LetterContent();
?>