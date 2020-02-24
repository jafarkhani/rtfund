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
ACC_docs::PrintDoc($docID);
?>
</center>