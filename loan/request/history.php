<?php
//---------------------------
// programmer:	Jafarkhani
// create Date:	91.12
//---------------------------
require_once("../../header.inc.php");
require_once inc_component;
$RequestID = $_POST["RequestID"];

$query = "select f.*,
				concat_ws(' ',fname, lname,CompanyName) fullname , 
				b.InfoDesc StatusDesc
			from LON_ReqFlow f 
				join BaseInfo b on (b.InfoID = f.StatusID AND TypeID=5) 
				join BSC_persons using(PersonID) 
				where f.RequestID=?
			order by FlowId";
$Logs = PdoDataAccess::runquery($query, array($RequestID));

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
				" . $Logs[$i]["StatusDesc"] . "</td>
			<td  width=150px>" . $Logs[$i]["fullname"] . "</td>
			<td width=110px>" . substr($Logs[$i]["ActDate"], 11) . " " . 
								DateModules::miladi_to_shamsi($Logs[$i]["ActDate"]) . "</td>
			<td><div style='cursor:pointer' class='qtip-target' data-qtip='" . 
				$Logs[$i]["StepComment"] . "'>" .
				str_ellipsis($Logs[$i]["StepComment"], 48). "</div></td>
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