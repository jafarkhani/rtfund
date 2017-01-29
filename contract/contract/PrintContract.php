<?php
//-----------------------------
//	Programmer	: Fatemipour
//	Date		: 94.08
//-----------------------------
require_once '../header.inc.php';
require_once 'contract.class.php';
require_once '../templates/templates.class.php';
require_once inc_CurrencyModule;

$CntObj = new CNT_contracts($_REQUEST['ContractID']);
$st = $CntObj->GetContractContext();
//---------------------------------------------------------
$signs = CNT_ContractSigns::Get(" AND ContractID=?", array($CntObj->ContractID));
?>
<head>
    <meta content="text/html; charset=utf-8" http-equiv="Content-Type"/>	
	<link rel="stylesheet" type="text/css" href="/generalUI/fonts/fonts.css">
    <style media="print">
        .noPrint {display:none;}
		@page  
		{ 
			size: auto;   /* auto is the initial value */ 

			/* this affects the margin in the printer settings */ 
			margin: 10mm 0mm 10mm 0mm;  
		} 
    </style>
    <style type="text/css">
        body {font-family: nazanin;font-size: 10pt;margin: 20px}
        td	 {
			padding: 4px 30px 10px 30px;
			font-size: 11pt; 
			/*text-indent : 20px; */
			text-align: justify; 
			line-height : 2;}
		table { page-break-inside:auto; }
		tr    { /*page-break-inside:avoid;*/ page-break-after:auto }
		thead { display:table-header-group }
		tfoot { display:table-footer-group;  }
		
	</style>
</head>

<body dir="rtl">
    <table style='border:2px dashed #AAAAAA;border-collapse:collapse;width:19cm;height:100%' align='center'>
		<thead>
			<tr style="margin-top:40px">
				<td width=180px style='text-indent : 0;padding:0; '>
					<img style="width:150px" src='/framework/icons/logo.jpg'></td>
				<td align='center' style='font-family:Titr;font-size:18px;text-align:center !important;'>
					<b><?php
						echo $CntObj->_TemplateTitle;
						echo '<br>';
						echo $CntObj->description;
						?></b>
				</td>
				<td width=180px style='text-align:center;text-indent : 0;padding:0'>شماره قرارداد : <?= $CntObj->ContractID ?>
					<br> تاریخ ثبت قرارداد :  <?= DateModules::shNow() ?>
				</td>
			</tr>
		</thead>
		<tr>
			<td colspan="3" style="vertical-align: top">
				<?= $st ?>
			</td>
		</tr>
		<tfoot>
			<tr>
				<td colspan="3" style="padding:0;height:80px;padding-right:20px;">
					<?
						if(count($signs) > 0)
							$width = round(100/count($signs));
						foreach($signs as $row)
						{
							echo "<div style='float:left;width:$width%;font-weight:bold'>
									" . $row["fullname"] . $row["SignerName"] . "
									<br>
									" . $row["SignerPost"] . "
								</div>";
						}
					?>
				</td>
			</tr>
			<tr>
				<td colspan="3" style="padding:0;height:150px;padding-right:20px;padding-left: 20px;">
					<hr>
					<b>نشانی :</b><br>
					<b>شعبه پارک علم و فناوری خراسان : </b> مشهد، کیلومتر 12 جاده قوچان، روبروی شیر پگاه
					<b> تلفن : 5003441 - فکس : 5003409 </b>
					<br>
					<b>شعبه دانشگاه فردوسی مشهد : </b>پردیس، درب غربی( ورودی شهید باهنر ) 
					<b>تلفن : 38837392 - فکس : 38837392</b>
					<br>سایت : <b>www.krrtf.ir</b>
					<br>ایمیل : <b>krfn.ir@gmail.com</b>
				</td>
			</tr>
		</tfoot>
	</table>
</body>
