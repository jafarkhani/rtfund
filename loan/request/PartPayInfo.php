<?php
//---------------------------
// programmer:	Jafarkhani
// create Date:	91.12
//---------------------------
require_once("../../header.inc.php");
require_once 'request.class.php';
		
if(empty($_POST["PartID"]))
	die();

$PartID = $_POST["PartID"];

$PartObj = new LON_ReqParts($PartID);

$CostCode_commitment = COSTID_Commitment;
$dt = PdoDataAccess::runquery("
		select DocID,LocalNo,sum(CreditorAmount) amount,PartAmount,StatusID
		from ACC_DocItems 
			join ACC_docs using(DocID)
			join LON_ReqParts on(PartID = SourceID2)
		where CostID=? AND SourceID2=? AND SourceType=?
		group by DocID",
	array($CostCode_commitment, $PartID, DOCTYPE_LOAN_PAYMENT));
if(count($dt) == 0)
{
	$dt = PdoDataAccess::runquery("
		select DocID,LocalNo,sum(CreditorAmount) amount,PartAmount,StatusID
		from ACC_DocItems 
			join ACC_docs using(DocID)
			join LON_ReqParts on(PartID = SourceID2)
		where CostID=? AND SourceID2=? AND SourceType=?
		group by DocID",
	array(2, $PartID, DOCTYPE_LOAN_PAYMENT));
}
$tbl_content = "";
foreach($dt as $row)
{
	switch($row["StatusID"])
	{
		case ACC_STEPID_RAW :		$status = "خام"; break;
		case ACC_STEPID_CONFIRM :	$status = "قطعی شده"; break;
		default :					$status = "در مسیر گردش";
	}
	$tbl_content .= 
		"<tr><td width=85px>شماره سند :</td><td class=blueText>" . $row["LocalNo"] . "</td><tr>".
		"<tr><td>مبلغ پرداخت :</td><td class=blueText>" . number_format($row["amount"]) . "</td></tr>".
		"<tr><td>وضعیت سند :</td><td class=blueText>" . $status . "</td></tr>".
		($row["StatusID"] == ACC_STEPID_RAW ? 
			"<tr><td colspan=2 align=left><button class=x-btn ".
				"onclick=RequestInfoObject.ReturnPayPart(" . $row["DocID"] . ") >برگشت</button></td></tr>" : "") .
		"<tr><td colspan=2 style=background-color:yellowgreen;height:15px;>&nbsp;</td></tr>";
}

$temp = PdoDataAccess::runquery("select ifnull(sum(CreditorAmount-DebtorAmount),0)
	from ACC_DocItems 
	where CostID=? AND SourceType=? AND SourceID2=?",
	array($CostCode_commitment, DOCTYPE_LOAN_PAYMENT, $PartID));

$MaxAvailablePayAmount = $PartObj->PartAmount*1 - $temp[0][0];		
if(count($dt) > 0 && $temp[0][0] == 0)
	$MaxAvailablePayAmount = 0;
$tbl_content .= "<tr><td>مبلغ قابل پرداخت :</td><td class=blueText>" . number_format($MaxAvailablePayAmount) . "</td></tr>";
$tbl_content .= ($MaxAvailablePayAmount > 0) ? "<tr><td colspan=2 align=center><button class=x-btn ".
	"onclick=RequestInfoObject.PayPart(" . $MaxAvailablePayAmount . ")>پرداخت</button></td></tr>" : "";
?>
<style>
.infotd td{padding-right:4px; height: 21px;}
</style>
<div style="background-color:white;width: 100%;" align="center">
	<br>
	<table class="infotd" style="border: solid 1px #e8e8e8;border-collapse: collapse" width="80%" bgcolor="white" cellpadding="0" cellspacing="0">
		<?= $tbl_content ?>
	</table>
</div>
<br>&nbsp;