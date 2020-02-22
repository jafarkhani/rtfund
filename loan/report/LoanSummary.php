<?php
//-----------------------------
//	Programmer	: SH.Jafarkhani
//	Date		: 94.12
//-----------------------------

require_once '../../header.inc.php';
require_once "ReportGenerator.class.php";
require_once '../request/request.class.php';
require_once '../request/request.data.php';

$RequestID = $_REQUEST["RequestID"];
$ReqObj = new LON_requests($RequestID);
$PartObj = LON_ReqParts::GetValidPartObj($RequestID);

$temp = PdoDataAccess::runquery("
	select b1.infoDesc DocTypeDesc
			from DMS_documents d
			join BaseInfo b1 on(InfoID=d.DocType AND TypeID=8)
			where ObjectType='loan'  AND b1.param1=1 AND ObjectID=? group by d.DocType", array($RequestID));
$docs = array();
foreach($temp as $row)
	$docs[] = $row["DocTypeDesc"];

BeginReport();

?>
<style>
	.TBLheader{
		background-color: gainsboro;
		font-weight: bold;
	}
	td {
		padding: 0 2px 0 2px;
	}
</style>
<center>
	<br>
<table style="border:2px groove #9BB1CD;border-collapse:collapse;width:19cm;font-family: nazanin;
		font-size: 16px;line-height: 20px;">
	<tr>
		<td width=60px><img src='/framework/icons/logo.jpg' style='width:60px'></td>
		<td align="center" style="font-family: titr">کاردکس پرداخت تسهیلات</td>
		<td width=60px></td>
	</tr>
	<tr>
		<td colspan="3" align="center">
			<hr>
			<table width="98%" border="1" style="border-collapse: collapse;" cellpadding="2">
				<tr> 
					<td width="18%">نام شرکت : </td>
					<td width="32%"><b><?= $ReqObj->_LoanPersonFullname ?></b></td>
					<td width="18%">حامی / سرمایه گذار :</td>
					<td width="32%"><b><?= $ReqObj->_ReqPersonFullname ?></b></td>
				</tr>
				<tr>
					<td>مبلغ تسهیلات : </td>
					<td><b><?= number_format($PartObj->PartAmount) ?> ریال</b></td>
					<td>نرخ کارمزد :</td>
					<td><b><?= $PartObj->CustomerWage ?> %</b></td>
				</tr>
				<tr>
					<td>نوع تسهیلات : </td>
					<td><b><?= $ReqObj->_LoanDesc ?></b></td>
					<td>دوره تنفس:</td>
					<td><b><?= $PartObj->DelayMonths ?> ماه و <?= $PartObj->DelayDays ?> روز </b></td>
				</tr>
				<tr>
					<td>مدت بازپرداخت :</td>
					<td><b><?= $PartObj->PayInterval*$PartObj->InstallmentCount ?> 
						<?= $PartObj->IntervalType == "MONTH" ? "ماه" : "روز" ?></b></td>
					<td>تعداد اقساط : </td>
					<td><b><?= $PartObj->InstallmentCount ?> </b></td>
				</tr>
				<tr>
					<td>تضمینات : </td>
					<td colspan="3"><?= implode(" , ",$docs) ?></td>
				</tr>	
				<tr>
					<td>زیرواحد سرمایه گذار:</td>
					<td colspan="3"><?= $ReqObj->_SubAgentDesc ?></td>
				</tr>
			</table>
			<br><b>	مراحل پرداخت </b>
			<table width="98%" border="1" style="border-collapse: collapse;">
				<tr class="TBLheader">
					<td>ردیف</td>
					<td>تاریخ پرداخت</td>
					<td>مبلغ پرداخت</td>
				</tr>
				<?
					$dt = LON_payments::Get(" AND RequestID=?", array($RequestID));
					$index = 1;
					$sum = 0;
					foreach($dt as $row)
					{
						echo "<tr><td>" . $index++ . "</td><td>" . 
							DateModules::miladi_to_shamsi($row["PayDate"]) . 
							"</td><td>" . number_format($row["PayAmount"]) . "</td></tr>";
						$sum += $row["PayAmount"]*1;
					}
				?>
				<tr class="TBLheader">
					<td colspan="2" align="left">جمع : </td>
					<td><?= number_format($sum) ?></td>
				</tr>
			</table>
			<br><b>	مراحل باز پرداخت </b>
			<table width="98%" border="1" style="border-collapse: collapse;">
				<tr class="TBLheader">
					<td>ردیف</td>
					<td>تاریخ سررسید</td>
					<td>مبلغ</td>
					<td>ردیف</td>
					<td>تاریخ سررسید</td>
					<td>مبلغ</td>
				</tr>
				<?
					$dt = LON_installments::SelectAll("r.RequestID=? AND history='NO' AND 
						IsDelayed='NO'", array($RequestID));
					//print_r(ExceptionHandler::PopAllExceptions());
					$index = 1;
					$sum = 0;
					foreach($dt as $row)
					{
						if($index % 2 != 0)
							echo "<tr>";
						echo "<td>" . $index++ . "</td><td>" . 
							DateModules::miladi_to_shamsi($row["InstallmentDate"]) . 
							"</td><td>" . number_format($row["InstallmentAmount"]) . "</td>";
						$sum += $row["InstallmentAmount"]*1;
						if($index % 2 != 0)
							echo "</tr>";
					}
					if($index % 2 == 0)
						echo "<td></td></tr>";
				?>
				<tr class="TBLheader">
					<td colspan="5" align="left">جمع : </td>
					<td><?= number_format($sum) ?></td>
				</tr>
			</table>
			<br><b>پرداخت های مشتری </b>
			<table width="98%" border="1" style="border-collapse: collapse;">
				<tr class="TBLheader">
					<td>ردیف</td>
					<td>تاریخ پرداخت</td>
					<td>نوع پرداخت</td>
					<td>بابت</td>
					<td>وضعیت چک</td>
					<td>مبلغ</td>
					<td>شماره فیش/پیگیری/چک</td>
				</tr>
				<?
				ini_set("display_errors", "On");
				$index = 1;
				$sum = 0;
				require_once '../../accounting/cheque/cheque.class.php';
				$dt = ACC_IncomeCheques::Get(" AND LoanRequestID=? order by ChequeDate", array($RequestID));
				$dt = $dt->fetchAll();
				foreach($dt as $row)
				{
					$color = $row["ChequeStatus"] != INCOMECHEQUE_VOSUL ? "gray" : "black";

					echo "<tr><td>" . $index++ . 
						"</td><td>" . DateModules::miladi_to_shamsi($row["ChequeDate"]) .
						"</td><td> چک" .
						"</td><td>تنفس" .  
						"</td><td>" . $row["ChequeStatusDesc"] . 							
						"</td><td style=color:$color>" . number_format($row["ChequeAmount"]) . "</td>" . 
						"</td><td>" . $row["ChequeNo"] . 
						"</td></tr>";
				}
				//----------------------------------------------
				$dt = LON_BackPays::SelectAll("RequestID=? order by PayDate", array($RequestID));
				foreach($dt as $row)
				{
					$color = $row["PayType"] == BACKPAY_PAYTYPE_CHEQUE && $row["ChequeStatus"] != INCOMECHEQUE_VOSUL ? "gray" : "black";

					echo "<tr><td>" . $index++ . 
						"</td><td>" . DateModules::miladi_to_shamsi($row["PayDate"]) .
						"</td><td>" . $row["PayTypeDesc"] . 
						"</td><td>قسط" .  
						"</td><td>" . $row["ChequeStatusDesc"] . 							
						"</td><td style=color:$color>" . number_format($row["PayAmount"]) . "</td>" . 
						"</td><td>" . $row["PayBillNo"] . $row["PayRefNo"] . $row["ChequeNo"] . 
						"</td></tr>";

					if($row["PayType"] != BACKPAY_PAYTYPE_CHEQUE)
						$sum += $row["PayAmount"]*1;
					else if($row["ChequeStatus"] == INCOMECHEQUE_VOSUL)
							$sum += $row["PayAmount"]*1;
				}
				?>
				<tr class="TBLheader">
					<td colspan="5" align="left">جمع پرداختی: </td>
					<td colspan="2"><?= number_format($sum) ?></td>
				</tr>
			</table>
			<br>
		</td> 
	</tr>
</table>	
</center>