<?php
//---------------------------
// programmer:	b.Mahdipour
// Date:		94.11
//---------------------------
require_once '../../../header.inc.php';
require_once inc_dataReader;
require_once inc_manage_unit;

$drp_month = manage_domains::DRP_months("tax_n_m");

require_once '../js/payment_process.js.php';
?>
<form method="post" id="mainForm">
	<center>
		<div>
		<br>
		<div id="issuePayment_DIV" style="width: 750px">
			<table id="issuePayment_TBL" style="width: 100%">
							
				<tr>
					<td>شماره شناسایی از :</td>
					<td><input type="text" name="from_staff_id" id="from_staff_id"></td>
					<td>تا :</td>
					<td><input type="text" name="to_staff_id" id="to_staff_id" ></td>
				</tr>
				<tr>
					<td>از تاریخ :</td>
					<td><input type="text" name="start_date" id="start_date"></td>
					<td>تا تاریخ :</td>
					<td><input type="text" name="end_date" id="end_date"></td>
				</tr>				
				<tr>
					<td>شروع تعديل ماليات از سال:</td>
					<td><input type="text" name="tax_normalized_year" id="tax_normalized_year" class="x-form-text x-form-field" value="1399"></td>
					<td>شروع تعديل ماليات از ماه:</td>
					<td><?= $drp_month ?></td>
				</tr>
				<tr>
					<td colspan="2" style="height:21px">
						<input type="checkbox" name="compute_backpay" id="compute_backpay" value ="1" checked  >
						backpay محاسبه شود ؟
					</td>
					<td colspan="2">
						<input type="checkbox" name="tax_normalize" id="tax_normalize" value ="1" checked >
						تعديل ماليات انجام شود؟
					</td>
				</tr>
												
				<tr>
					<td colspan="4" style="height:21px">
					<input type="checkbox" name="negative_fiche" id="negative_fiche" value ="1"  >
						محاسبه فيشهاي منفي (هشدار! اين گزينه صرفا جهت كنترل فيشهاي منفي گذاشته شده است. لطفا در استفاده از آن دقت نماييد.)
					</td>					
				</tr>
			
			</table>
		</div>
		</div>
		<!-- ------------------------------------------------------------ -->
		<div align="right" class="panel" id="result" style="display:none;width: 750px;">
			<br>
			<img src="/HumanResources/img/loading.gif" id="img_loading" style="vertical-align: middle">
				<font style="font-size:'10px';font-weight: bold" color="#194775" > &nbsp;&nbsp;&nbsp;
				محاسبه حقوق <br>
				<hr size="3" width="50%" noshade align="right" style="color:#66A3E0" >				
				</font>
				
				<div id="result_data"></div>
		</div>
		
		
		<!-- ------------------------------------------------------------ -->
	</center>
</form>
</body>
</html>