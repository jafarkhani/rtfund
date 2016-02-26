<?php
//-----------------------------
//	Programmer	: SH.Jafarkhani
//	Date		: 1394.10
//-----------------------------
require_once 'header.inc.php';

$IsStaff = $_SESSION["USER"]["IsStaff"] == "YES";
$IsCustomer = $_SESSION["USER"]["IsCustomer"] == "YES";
$IsShareholder = $_SESSION["USER"]["IsShareholder"] == "YES";
$IsAgent = $_SESSION["USER"]["IsAgent"] == "YES";
$IsSupporter = $_SESSION["USER"]["IsSupporter"] == "YES";

$loansText = "";

if($IsCustomer)
{
	$dt = PdoDataAccess::runquery("select * from LON_requests 
		where StatusID<70 AND LoanPersonID=? 
		AND (ReqPersonID<>LoanPersonID or SupportPersonID>0)",
		array($_SESSION["USER"]["PersonID"]));
	if(count($dt) == 0)
		$loansText = "هنوز وامی به نام شما به صندوق معرفی نشده است";
	else
		$loansText = "<a class='menuItem blueText' style='text-align:center;font-size:12px' ".
			"href='javascript:StartPageObject.OpenLoan(" . 
			$dt[0]["RequestID"] . ")'>" . "وامی به مبلغ " . 
			number_format($dt[0]["ReqAmount"]) . " به نام شما تعریف شده است. [ مشاهده جزئیات ]".
			"</a> برای پیگیری های بعدی می توانید از منوی وام های دریافتی وضعیت وام خود را پیگیری کنید";
}
?>
<script>

StartPage.prototype = {
	TabID : '<?= $_REQUEST["ExtTabID"]?>',
	address_prefix : "<?= $js_prefix_address?>",

	IsStaff :		<?= $IsStaff ? "true" : "false" ?>,
	IsCustomer :	<?= $IsCustomer ? "true" : "false" ?>,
	IsShareholder : <?= $IsShareholder ? "true" : "false" ?>,
	IsAgent :		<?= $IsAgent ? "true" : "false" ?>,
	IsSupporte :	<?= $IsSupporter ? "true" : "false" ?>,

	get : function(elementID){
		return findChild(this.TabID, elementID);
	}
};

function StartPage(){
	
	if(this.IsCustomer)
	{
		new Ext.form.FieldSet({
			title : "وام هایی معرفی شده",
			width : 700,
			html : "<?= $loansText ?>",
			applyTo : this.get("div_loans")
		});
	}
}

StartPageObject = new StartPage();

StartPage.prototype.OpenLoan = function(RequestID){

	portal.OpenPage("/loan/request/RequestInfo.php", {RequestID : RequestID});
}

</script>
<center><br>
	<div class="blueText">کاربر گرامی <br> به پرتال جامع <?= SoftwareName ?> خوش آمدید. 
		<?
			if($_SESSION["USER"]["IsActive"] == "PENDING")
			{
				echo "<br><br>" . "امکانات پرتال زمانی برای شما فعال خواهد گردید که ثبت نام شما توسط صندوق تایید گردد.";
			}
		?>
	</div>	
	<br>
	<div id="div_loans"></div>
</center>