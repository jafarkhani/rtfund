<?php
//-----------------------------
//	Programmer	: SH.Jafarkhani
//	Date		: 1394.10
//-----------------------------
require_once 'header.inc.php';
require_once inc_dataReader;
require_once 'letter/letter.class.php';
require_once 'workflow/wfm.class.php';

$dt = OFC_letters::SelectReceivedLetters(" AND s.IsDeleted='NO' AND s.IsSeen='NO'");
$NewReceived = $dt->rowCount();

$dt = OFC_letters::SelectDraftLetters();
$DraftCount = count($dt);

$dt = WFM_FlowRows::SelectReceivedForms(); 
$ReceiveForms = is_array($dt) ? count($dt) : $dt->rowCount();

$Messages = OFC_MessageReceivers::GetNewMessageReceiveCount();
?>
<script>

OfficeStartPage.prototype = {
	TabID : '<?= $_REQUEST["ExtTabID"]?>',
	address_prefix : "<?= $js_prefix_address?>",

	get : function(elementID){
		return findChild(this.TabID, elementID);
	}
};

function OfficeStartPage(){
		
}

OfficeStartPageObject = new OfficeStartPage();

OfficeStartPage.prototype.OpenPage = function(mode){
	
	if(mode == "receive")
		framework.OpenPage("/office/letter/MyLetter.php","نامه های رسیده", {mode : mode});
	if(mode == "draft")
		framework.OpenPage("/office/letter/DraftLetters.php","نامه های پیش نویس");
	if(mode == "form")
		framework.OpenPage("/office/workflow/MyForms.php","فرم های رسیده");
	if(mode == "message")
		framework.OpenPage("/office/letter/MyMessages.php","مدیریت پیام ها");
	
}

</script>
<style>
	#div_content td{
		height: 21px
	}
</style>
<center><br>
	<div id="div_summary" align="right">
		<table id="div_content" align="right" style="width:85%;margin : 0 10 10 0">
			<tr>
				<td><img src="/office/icons/summary.png" style="width:30px;vertical-align: middle;">
					نامه های رسیده جدید : 
					<a href="javascript:OfficeStartPageObject.OpenPage('receive')">( <?= $NewReceived ?> )</a>
				</td>
			</tr>
			<tr>
				<td><img src="/office/icons/summary.png" style="width:30px;vertical-align: middle;">
					نامه های پیش نویس : 
					<a href="javascript:OfficeStartPageObject.OpenPage('draft')">( <?= $DraftCount ?> )</a>
				</td>
			</tr>
			<tr>
				<td><img src="/office/icons/comment.png" style="width:30px;vertical-align: middle;">
					پیام های رسیده جدید :
					<a href="javascript:OfficeStartPageObject.OpenPage('message')">( <?= $Messages ?> )</a>
				</td>
			</tr>
			<tr>
				<td><hr></td>
			</tr>
			<tr>
				<td><img src="/office/icons/form.png" style="width:30px;vertical-align: middle;">
					فرم های منتظر تایید :
					<a href="javascript:OfficeStartPageObject.OpenPage('form')">( <?= $ReceiveForms ?> )</a>					
				</td>
			</tr>
		</table>
	</div>
</center>