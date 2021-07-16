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
				concat_ws(' ',fname, lname,CompanyName) fullname, f.IsTree
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
		else if($Logs[$i]["ActionType"] == "REJECT" && $Logs[$i]["IsTree"] == "NO" )
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
		
		$WhereClause = "" ; 
		$whereParam = array();
		$whereParam[':FID'] =  $FlowID ; 
		
		if($Logs[0]["IsTree"] == 'YES')
		{
			$WhereClause = " p.PersonID = :PID  " ; 
			$whereParam[':PID'] =  $_SESSION["USER"]["PersonID"] ; 
		}
		else
		{
			$WhereClause = " fs.StepID= :SID  " ; 
			$whereParam[':SID'] =  $StepID ; 
		}
		
		$query = "select StepDesc,PostName,
					concat_ws(' ',fname, lname,CompanyName) fullname
				from WFM_FlowSteps fs
				left join BSC_jobs j on(fs.JobID=j.JobID or fs.PostID=j.PostID)
				left join BSC_posts ps on(j.PostID=ps.PostID)
				left join BSC_persons p on(j.PersonID=p.PersonID or fs.PersonID=p.PersonID)
				where fs.IsActive='YES' AND fs.FlowID=:FID AND ".$WhereClause ;
		$nextOne = PdoDataAccess::runquery($query, $whereParam);
		
		if(count($nextOne)>0 || ( !empty($_REQUEST['TrackHis']) && $_REQUEST['TrackHis'] == 1 ) )
		{
			
			if($Logs[0]["IsTree"] != 'YES') 
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
			elseif(empty($_REQUEST['TrackHis'])) 
			{
				$str = "";				
				$str .= "<br>" . $nextOne[0]["fullname"] . 
					($nextOne[0]["PostName"] != "" ? " [ پست : " . $nextOne[0]["PostName"] . " ]" : "") . " و ";
				$str = substr($str, 0, strlen($str)-3);
				$tbl_content .= "<tr style='background-color:#A9E8E8'>
					<td colspan=4 align=center>در حال حاضر فرم در مرحله <b>" . 
					"بررسی</b>  در کارتابل <b>" . $str . "</b><br> می باشد.</td>
				</tr>";
			}
			else {
				
			
				$query = "  SELECT fs.StepDesc,ps.PostName,
								   concat_ws(' ',p.fname, p.lname,p.CompanyName) fullname ,
								   fs2.StepDesc StepDesc2 ,ps2.PostName PostName2,
								   concat_ws(' ',p2.fname, p2.lname,p2.CompanyName) fullname2, 
								   fr.IsEnded


							FROM WFM_FlowRows fr
							   LEFT JOIN  WFM_FlowSteps fs ON fr.StepRowID = fs.StepParentID
							   LEFT JOIN BSC_jobs j ON(fs.JobID=j.JobID or fs.PostID=j.PostID)
							   LEFT JOIN BSC_posts ps ON(j.PostID=ps.PostID)
							   LEFT JOIN BSC_persons p ON(j.PersonID=p.PersonID or fs.PersonID=p.PersonID)
							   
								LEFT JOIN WFM_FlowSteps fs2 ON fr.StepRowID = fs2.StepRowID  
								LEFT JOIN WFM_FlowSteps fs3 ON fs2.ReturnStep = fs3.StepRowID  
								LEFT JOIN BSC_jobs j2 ON(fs3.JobID=j2.JobID or fs3.PostID=j2.PostID)
								LEFT JOIN BSC_posts ps2 ON(j2.PostID=ps2.PostID)
								LEFT JOIN BSC_persons p2 ON(j2.PersonID=p2.PersonID or fs3.PersonID=p2.PersonID)

							WHERE fr.FlowID = :FID  and fr.ObjectID = :OID  and fr.IsLastRow = 'YES' and 
								  if(fs.StepDesc != '' , fs.LastStep = 0 , (1=1) ) " ; 
				$nextOne = PdoDataAccess::runquery($query , array(":FID" => $FlowID , ":OID" => $ObjectID ) );
				
				if($nextOne[0]["IsEnded"] == 'YES' ){
					$tbl_content .= "<tr style='background-color:#A9E8E8'>
									<td colspan=4 align=center><b>گردش فرم پایان یافته است.</b></td>
									</tr>";
				}
				else if(!empty($nextOne[0]["fullname"]))
				{
					$str = "";				
					$str .= "<br>" . $nextOne[0]["fullname"] . 
						($nextOne[0]["PostName"] != "" ? " [ پست : " . $nextOne[0]["PostName"] . " ]" : "") . " و ";
					$str = substr($str, 0, strlen($str)-3);
					$tbl_content .= "<tr style='background-color:#A9E8E8'>
						<td colspan=4 align=center>در حال حاضر فرم در مرحله <b>" . 
						"بررسی</b>  در کارتابل <b>" . $str . "</b><br> می باشد.</td>
					</tr>";
				}
				else {
					$str = "";				
					$str .= "<br>" . $nextOne[0]["fullname2"] . 
						($nextOne[0]["PostName2"] != "" ? " [ پست : " . $nextOne[0]["PostName2"] . " ]" : "") . " و ";
					$str = substr($str, 0, strlen($str)-3);
					$tbl_content .= "<tr style='background-color:#A9E8E8'>
						<td colspan=4 align=center>در حال حاضر فرم در مرحله <b>" . 
						"بررسی</b>  در کارتابل <b>" . $str . "</b><br> می باشد.</td>
					</tr>";
				}
		
			}
			
			
			
		}
		else
		{
			$tbl_content .= "<tr style='background-color:#A9E8E8'>
				<td colspan=4 align=center><b>گردش فرم پایان یافته است.</b></td>
				</tr>";
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