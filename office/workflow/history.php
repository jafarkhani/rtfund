<?php
//---------------------------
// programmer:	Jafarkhani
// create Date:	94.12
//---------------------------

require_once getenv("DOCUMENT_ROOT") . "/office/header.inc.php";
require_once getenv("DOCUMENT_ROOT") . "/office/workflow/wfm.class.php";
require_once getenv("DOCUMENT_ROOT") . "/office/workflow/form.class.php";
require_once inc_component;

if(!empty($_REQUEST["RowID"]))
{
	$FlowRowObj = new WFM_FlowRows($_REQUEST["RowID"]);
	$FlowID = $FlowRowObj->FlowID;
	$ObjectID = $FlowRowObj->ObjectID;
}
else if(!empty($_REQUEST["FlowID"]) && !empty($_REQUEST["ObjectID"]))
{
	$FlowID = $_REQUEST["FlowID"];
	$ObjectID = $_REQUEST["ObjectID"];
}
else if(!empty($_REQUEST["RequestID"]))
{
	$ReqObj = new WFM_requests($_REQUEST["RequestID"]);
	$FlowID = $ReqObj->_FlowID;
	$ObjectID = $ReqObj->RequestID;
}
else
	die();
	 
$query = "select fr.* ,fs.StepID, fs.IsOuter,bf.*,
				ifnull(fr.StepDesc, ifnull(fs.StepDesc,'ارسال اولیه')) StepDesc,
				concat_ws(' ',fname, lname,CompanyName) fullname
			from WFM_FlowRows fr
			join WFM_flows f using(FlowID)
			join BaseInfo bf on(bf.TypeID=11 AND f.ObjectType=bf.InfoID)
			left join WFM_FlowSteps fs on(fr.StepRowID=fs.StepRowID)
			join BSC_persons p on(fr.PersonID=p.PersonID)
			where fr.FlowID=? AND fr.ObjectID=?
			order by RowID";
$Logs = PdoDataAccess::runquery($query, array($FlowID, $ObjectID));

$tbl_content = "";

if(count($Logs) == 0)
{
	 $tbl_content = "<tr><td>فرم مورد نظر فاقد گردش می باشد</td></tr>";
}
else 
{
	for ($i=0; $i<count($Logs); $i++)
	{
		$backgroundColor = ($i%2 == 1 ? "style='background-color:#efefef'" : "");
		$backgroundColor = $Logs[$i]["ActionType"] == "REJECT" ? "style='background-color:#ffccd1'" : $backgroundColor;
		
		$StepDesc = $Logs[$i]["StepDesc"];
		if($Logs[$i]["StepID"] != "0")
		{
			if($Logs[$i]["ActionType"] == "CONFIRM")
				$StepDesc = "تایید " . $StepDesc;
			else if($Logs[$i]["ActionType"] == "REJECT")
				$StepDesc = "رد " . $StepDesc;
		}
		else if($Logs[$i]["ActionType"] == "REJECT")
			$StepDesc = "برگشت " . $StepDesc;
			
		
		$tbl_content .= "<tr " . $backgroundColor . ">
			<td width=250px>[" . ($i+1) . "]". ($i+1<10 ? "&nbsp;" : "") . "&nbsp;
				<img align='top' src='/generalUI/ext4/resources/themes/icons/user_comment.gif'>&nbsp;" . $StepDesc . " </td>
			<td  width=150px>" . $Logs[$i]["fullname"] . "</td>
			<td width=110px>" . substr($Logs[$i]["ActionDate"], 11) . " " . 
								DateModules::miladi_to_shamsi($Logs[$i]["ActionDate"]) . "</td>
			<td><div style='cursor:pointer' class='qtip-target' data-qtip='" . 
				$Logs[$i]["ActionComment"] . "'>" .
				str_ellipsis($Logs[$i]["ActionComment"], 48). "</div></td>
		</tr>";
	}
	//------------------------ get next one ------------------------------------
	$LastRecord = $Logs[$i-1];
	/*if($LastRecord["param5"] != "")
	{
		$dt = PdoDataAccess::runquery("select " . $LastRecord["param6"] . " from " . $LastRecord["param5"] . "
			where " . $LastRecord["param2"] . "=?", array($LastRecord["ObjectID"]));
		if($dt[0][0] == $LastRecord["param7"])
			$tbl_content .= "<tr style='background-color:#A9E8E8'>
				<td colspan=4 align=center><b>گردش فرم پایان یافته است.</b></td>
				<tr>";
	}
	if($LastRecord["StepRowID"] != "")
	{*/
		$StepID = $LastRecord["StepID"] == "" ? 0 :
			($LastRecord["ActionType"] == "CONFIRM" ? $LastRecord["StepID"] + 1 : $LastRecord["StepID"] - 1);
		$query = "select StepDesc,PostName,
					concat_ws(' ',fname, lname,CompanyName) fullname
				from WFM_FlowSteps fs
				left join BSC_jobs j on(fs.JobID=j.JobID or fs.PostID=j.PostID)
				left join BSC_posts ps on(j.PostID=ps.PostID)
				left join BSC_persons p on(j.PersonID=p.PersonID or fs.PersonID=p.PersonID)
				where fs.IsActive='YES' AND fs.FlowID=? AND fs.StepID=?";
		$nextOne = PdoDataAccess::runquery($query, array($FlowID, $StepID));

		if(count($nextOne)>0)
		{
			$str = "";
			foreach($nextOne as $row)
				$str .= "<br>" . $row["fullname"] . 
					($row["PostName"] != "" ? " [ پست : " . $row["PostName"] . " ]" : "") . " و ";
			$str = substr($str, 0, strlen($str)-3);

			$tbl_content .= "<tr style='background-color:#A9E8E8'>
					<td colspan=4 align=center>در حال حاضر فرم در مرحله <b>" . 
					$nextOne[0]["StepDesc"] . "</b>  در کارتابل <b>" . $str . "</b><br> می باشد.</td>
				</tr>";
		}
		else
		{
			$tbl_content .= "<tr style='background-color:#A9E8E8'>
				<td colspan=4 align=center><b>گردش فرم پایان یافته است.</b></td>
				<tr>";
		}
	//}
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