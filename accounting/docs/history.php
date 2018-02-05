<?php
//---------------------------
// programmer:	Jafarkhani
// create Date:	95.08
//---------------------------

if(!empty($_POST["DocID"]))
{
	require_once getenv("DOCUMENT_ROOT") . '/definitions.inc.php';
	$FlowID = FLOWID_ACCDOC;
	
	$_REQUEST["FlowID"] = (int)$FlowID;
	$_REQUEST["ObjectID"] = $_POST["DocID"];

	require_once getenv("DOCUMENT_ROOT") . '/office/workflow/history.php';
	die();
}
die();

require_once("../header.inc.php");
require_once inc_component;

$DocID = $_POST["DocID"];

$query = "select h.*,
				concat_ws(' ',fname, lname,CompanyName) fullname 
			from ACC_DocHistory h 
				join BSC_persons using(PersonID) 
				where h.DocID=?
			order by RowID ";
$Logs = PdoDataAccess::runquery($query, array($DocID));

$tbl_content = "";

if(count($Logs) == 0)
{
	 $tbl_content = "<tr><td>فرم مورد نظر فاقد گردش می باشد</td></tr>";
}
else 
{
	for ($i=0; $i<count($Logs); $i++)
	{
		$tbl_content .= "<tr " . ($i%2 == 1 ? "style='background-color:#efefef'" : "") . ">
			<td width=250px>[" . ($i+1) . "]". ($i+1<10 ? "&nbsp;" : "") . "&nbsp;
				<img align='top' src='/generalUI/ext4/resources/themes/icons/user_comment.gif'>&nbsp;
				" . $Logs[$i]["description"] . "</td>
			<td  width=150px>" . $Logs[$i]["fullname"] . "</td>
			<td width=110px>" . substr($Logs[$i]["ActionDate"], 11) . " " . 
								DateModules::miladi_to_shamsi($Logs[$i]["ActionDate"]) . "</td>
		</tr>";
	}
}
?>
<style>
.infotd td{border-bottom: solid 1px #e8e8e8;padding-right:4px; height: 21px;}
</style>
<div style="background-color:white;width: 100%; height: 100%">
	<table class="infotd" width="100%" bgcolor="white" cellpadding="0" cellspacing="0">
		<?= $tbl_content ?>
	</table>
</div>