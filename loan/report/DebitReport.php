<?php
//-----------------------------
//	Programmer	: SH.Jafarkhani
//	Date		: 94.12
//-----------------------------

require_once '../../header.inc.php';
require_once "ReportGenerator.class.php";
require_once '../request/request.class.php'; 
require_once '../request/request.data.php';

ini_set("display_errors", "On");

if(isset($_REQUEST["show"]))
{
	$RequestID = $_REQUEST["RequestID"];
	$ReqObj = new LON_requests($RequestID);
	$partObj = LON_ReqParts::GetValidPartObj($RequestID);
	//............ get total loan amount ......................
	$TotalAmount = LON_installments::GetTotalInstallmentsAmount($RequestID);
	//............ get remain untill now ......................
	$ComputeDate = !empty($_REQUEST["ComputeDate"]) ? $_REQUEST["ComputeDate"] : "";
	$ComputeDateStr = $ComputeDate == "" ? DateModules::shNow() : $ComputeDate;
	$GComputeDate = DateModules::shamsi_to_miladi($ComputeDateStr, "-");
	
	$ComputePenalty = !empty($_REQUEST["ComputePenalty"]) && $_REQUEST["ComputePenalty"] == "false" ? 
			false : true;
	$ComputeArr = LON_Computes::ComputePayments($RequestID, $ComputeDate, null, $ComputePenalty);
	
	//if($_SESSION['USER']["UserName"] == "admin")
	//	print_r($ComputeArr);
	
	$PureArr = LON_Computes::ComputePures($RequestID); 
	//............ get remain untill now ......................
	$CurrentRemain = LON_Computes::GetCurrentRemainAmount($RequestID, $ComputeArr);
	$TotalRemain = LON_Computes::GetTotalRemainAmount($RequestID, $ComputeArr);
	$DefrayAmount = 0;//LON_Computes::GetDefrayAmount($RequestID, $ComputeArr, $PureArr);
	$remains = LON_Computes::GetRemainAmounts($RequestID, $ComputeArr);
	//............. get total payed .............................
	$dt = LON_BackPays::GetRealPaid($RequestID);
	$totalPayed = 0;
	foreach($dt as $row)
		$totalPayed += $row["PayAmount"]*1;
	//............................................................
	if($ReqObj->IsEnded == "YES")
	{
		$CurrentRemain = "وام خاتمه یافته";
		$TotalRemain = "وام خاتمه یافته";
		$DefrayAmount = "وام خاتمه یافته";
	}
	else if($ReqObj->StatusID == LON_REQ_STATUS_DEFRAY)
	{
		$CurrentRemain = "وام تسویه شده است";
		$TotalRemain = "وام تسویه شده است";
		$DefrayAmount = "وام تسویه شده است";
	}
	else
	{
		$CurrentRemain = number_format($CurrentRemain) . " ریال";
		$TotalRemain = number_format($TotalRemain) . " ریال";
		$DefrayAmount = number_format($DefrayAmount) . " ریال";
	}
	//............................................................
		
	$returnArr = array();
	$totals = array(
		"compute" => array(
			"type" => "sum",
			"debt_pure" => 0,
			"debt_wage" => 0,
			"debt_late" => 0,
			"debt_pnlt" => 0,
			"debt_early" => 0,
			"debt_total" => 0
		),
		"payed" => array(
			"debt_pure" => 0,
			"debt_wage" => 0,
			"debt_late" => 0,
			"debt_pnlt" => 0,
			"debt_early" => 0,
			"debt_total" => 0
		),
		"remain" => array(
			"debt_pure" => 0,
			"debt_wage" => 0,
			"debt_late" => 0,
			"debt_pnlt" => 0,
			"debt_early" => 0,
			"debt_total" => 0
		),
		"totalremain" => array(
			"debt_pure" => 0,
			"debt_wage" => 0,
			"debt_late" => 0,
			"debt_pnlt" => 0,
			"debt_early" => 0,
			"debt_total" => 0
		)
	);
	for($i=0; $i<count($ComputeArr); $i++)
	{
		if($ComputeArr[$i]["type"] == "pay")
		{
			$returnArr[] = array_merge($ComputeArr[$i], array(
				"sep_pure" => $ComputeArr[$i]["pure"],
				"sep_wage" => $ComputeArr[$i]["wage"],
				"sep_late" => $ComputeArr[$i]["totallate"],
				"sep_pnlt" => $ComputeArr[$i]["totalpnlt"],
				
				"DebitType" => "",
				"debt_pure" => "",
				"debt_wage" => "",
				"debt_late" => "",
				"debt_pnlt" => "",
				"debt_early" => "",
				"debt_total" => ""
			));
			continue;
		}
		
		if($ComputeArr[$i]["RecordDate"] > $GComputeDate)
		{
			$returnArr[] = array_merge($ComputeArr[$i], array(
				"sep_pure" => $ComputeArr[$i]["pure"],
				"sep_wage" => $ComputeArr[$i]["wage"],
				"sep_late" => 0,
				"sep_pnlt" => 0,

				"DebitType" => "",
				"debt_pure" => 0,
				"debt_wage" => 0,
				"debt_late" => 0,
				"debt_pnlt" => 0,
				"debt_early" => 0,
				"debt_total" => 0
			));
			
			$totals["totalremain"]["debt_pure"] += $ComputeArr[$i]["remain_pure"];
			$totals["totalremain"]["debt_wage"] += $ComputeArr[$i]["remain_wage"];
			$totals["totalremain"]["debt_total"] += $ComputeArr[$i]["remain_pure"] + $ComputeArr[$i]["remain_wage"];
			
			continue;
		}
		
		$late = 0;
		$pnlt = 0;
		$payedLate = 0;
		$PayedPnlt = 0;
		foreach($ComputeArr[$i]["pays"] as $r)
		{
			$late += $r["cur_late"];
			$pnlt += $r["cur_pnlt"];
			$payedLate += $r["pay_late"];
			$PayedPnlt += $r["pay_pnlt"];
		}
		
		$record = array_merge($ComputeArr[$i], array(
			"sep_pure" => $ComputeArr[$i]["pure"],
			"sep_wage" => $ComputeArr[$i]["wage"],
			"sep_late" => 0,
			"sep_pnlt" => 0,
			
			"DebitType" => "محاسبه شده",
			"debt_pure" => $ComputeArr[$i]["pure"],
			"debt_wage" => $ComputeArr[$i]["wage"],
			"debt_late" => $late,
			"debt_pnlt" => $pnlt,
			"debt_early" => $ComputeArr[$i]["early"],
			"debt_total" => $ComputeArr[$i]["pure"] + $ComputeArr[$i]["wage"] + 
				$late + $pnlt + $ComputeArr[$i]["early"]
		));
		$returnArr[] = $record;
		$totals["compute"]["debt_pure"] += $record["debt_pure"];
		$totals["compute"]["debt_wage"] += $record["debt_wage"];
		$totals["compute"]["debt_late"] += $record["debt_late"];
		$totals["compute"]["debt_pnlt"] += $record["debt_pnlt"];
		$totals["compute"]["debt_early"] += $record["debt_early"];
		$totals["compute"]["debt_total"] += $record["debt_total"];
		
		
		$record = array_merge($ComputeArr[$i], array(
			"sep_pure" => $ComputeArr[$i]["pure"],
			"sep_wage" => $ComputeArr[$i]["wage"],
			"sep_late" => 0,
			"sep_pnlt" => 0,
			
			"DebitType" => "پرداخت شده",
			"debt_pure" => $ComputeArr[$i]["pure"] - $ComputeArr[$i]["remain_pure"],
			"debt_wage" => $ComputeArr[$i]["wage"] - $ComputeArr[$i]["remain_wage"],
			"debt_late" => $payedLate,
			"debt_pnlt" => $PayedPnlt,
			"debt_early" => $ComputeArr[$i]["early"],
			"debt_total" => $ComputeArr[$i]["pure"] - $ComputeArr[$i]["remain_pure"] + 
				$ComputeArr[$i]["wage"] - $ComputeArr[$i]["remain_wage"] +
				$payedLate + $PayedPnlt + $ComputeArr[$i]["early"]
				 
		));
		$returnArr[] = $record;
		$totals["payed"]["debt_pure"] += $record["debt_pure"];
		$totals["payed"]["debt_wage"] += $record["debt_wage"];
		$totals["payed"]["debt_late"] += $record["debt_late"];
		$totals["payed"]["debt_pnlt"] += $record["debt_pnlt"];
		$totals["payed"]["debt_early"] += $record["debt_early"];
		$totals["payed"]["debt_total"] += $record["debt_total"];
		
		
		$record = array_merge($ComputeArr[$i], array(
			"sep_pure" => $ComputeArr[$i]["pure"],
			"sep_wage" => $ComputeArr[$i]["wage"],
			"sep_late" => 0,
			"sep_pnlt" => 0,
			
			"DebitType" => "مانده",	
			"debt_pure" => $ComputeArr[$i]["remain_pure"],
			"debt_wage" => $ComputeArr[$i]["remain_wage"],
			"debt_late" => $ComputeArr[$i]["remain_late"],
			"debt_pnlt" => $ComputeArr[$i]["remain_pnlt"],
			"debt_early" => 0,
			"debt_total" => $ComputeArr[$i]["remain_pure"] + $ComputeArr[$i]["remain_wage"] + 
				$ComputeArr[$i]["remain_late"] + $ComputeArr[$i]["remain_pnlt"] 
		));	
		$returnArr[] = $record;
		$totals["remain"]["debt_pure"] += $record["debt_pure"];
		$totals["remain"]["debt_wage"] += $record["debt_wage"];
		$totals["remain"]["debt_late"] += $record["debt_late"];
		$totals["remain"]["debt_pnlt"] += $record["debt_pnlt"];
		$totals["remain"]["debt_early"] += $record["debt_early"];
		$totals["remain"]["debt_total"] += $record["debt_total"];
		
		$totals["totalremain"]["debt_pure"] += $ComputeArr[$i]["remain_pure"];
		$totals["totalremain"]["debt_wage"] += $ComputeArr[$i]["remain_wage"];
		$totals["totalremain"]["debt_late"] += $ComputeArr[$i]["remain_late"];
		$totals["totalremain"]["debt_pnlt"] += $ComputeArr[$i]["remain_pnlt"];
		$totals["totalremain"]["debt_early"] += 0;
		$totals["totalremain"]["debt_total"] += $ComputeArr[$i]["remain_pure"] + 
				$ComputeArr[$i]["remain_wage"] + 
				$ComputeArr[$i]["remain_late"] + $ComputeArr[$i]["remain_pnlt"];
	}
	//............................................................
	$rpg = new ReportGenerator();
	$rpg->mysql_resource = $returnArr;
	$rpg->rowNumber = false;
	
	function RowColorRender($row){
		return $row["type"] == "pay" ? "#fcfcb6" : "";
	}
	$rpg->rowColorRender = "RowColorRender";
	
	$col = $rpg->addColumn("", "id", "");
	$col->hidden = true;
	
	function ActionRender($row, $value){
		if($value == "installment")
		{
			if($row["id"] == "0")
				return  $row["details"];
			return "قسط" ;
		}
		return "پرداخت " . $row["details"];
	}
	$col = $rpg->addColumn("نوع عملیات", "type", "ActionRender");
	$col->rowspanByFields = array("id");
	$col->rowspaning = true;
	$col->align = "center";
	
	$col = $rpg->addColumn("تاریخ عملیات", "RecordDate","ReportDateRender");
	$col->rowspanByFields = array("RecordDate", "type","id");
	$col->rowspaning = true;
	$col->align = "center";
	
	$col = $rpg->addColumn("کل مبلغ", "RecordAmount","ReportMoneyRender");
	$col->rowspanByFields = array("RecordDate", "type","id");
	$col->rowspaning = true;
	$col->align = "center";
	
	$col = $rpg->addColumn("اصل مبلغ", "sep_pure","ReportMoneyRender");
	$col->GroupHeader = "تجزیه کل مبلغ در تاریخ عملیات";
	$col->rowspanByFields = array("RecordDate", "type","id");
	$col->rowspaning = true;
	$col->align = "center";
	
	$col = $rpg->addColumn("کارمزد", "sep_wage","ReportMoneyRender");
	$col->GroupHeader = "تجزیه کل مبلغ در تاریخ عملیات";
	$col->rowspanByFields = array("RecordDate", "type","BackPayID");
	$col->rowspaning = true;
	$col->align = "center";
	
	$col = $rpg->addColumn("کارمزد تاخیر", "sep_late","ReportMoneyRender");
	$col->GroupHeader = "تجزیه کل مبلغ در تاریخ عملیات";
	$col->rowspanByFields = array("RecordDate", "type","BackPayID");
	$col->rowspaning = true;
	$col->align = "center";
	
	$col = $rpg->addColumn("جریمه", "sep_pnlt","ReportMoneyRender");
	$col->GroupHeader = "تجزیه کل مبلغ در تاریخ عملیات";
	$col->rowspanByFields = array("RecordDate", "type","BackPayID");
	$col->rowspaning = true;
	$col->align = "center";

	function DebitStyleRender($row, $column){
		if($row["DebitType"] == "مانده")
			return "background-color:lightgreen";
		return "";
	}
	$col = $rpg->addColumn("نوع بدهی", "DebitType","");
	$col->style = "DebitStyleRender";
	$col->align = "center";
	
	$col = $rpg->addColumn("اصل", "debt_pure","ReportMoneyRender");
	$col->rowspanByFields = array("RecordDate");
	$col->GroupHeader = "وضعیت بدهی در تاريخ " . $ComputeDateStr;
	$col->style = "DebitStyleRender";
	$col->align = "center";
	
	$col = $rpg->addColumn("کارمزد", "debt_wage","ReportMoneyRender");
	$col->rowspanByFields = array("RecordDate");
	$col->GroupHeader = "وضعیت بدهی در تاريخ " . $ComputeDateStr;
	$col->style = "DebitStyleRender";
	$col->align = "center";
	
	$col = $rpg->addColumn("کارمزد تاخیر", "debt_late","ReportMoneyRender");
	$col->rowspanByFields = array("RecordDate");
	$col->GroupHeader = "وضعیت بدهی در تاريخ " . $ComputeDateStr;
	$col->style = "DebitStyleRender";
	$col->align = "center";
	
	$col = $rpg->addColumn("جریمه", "debt_pnlt","ReportMoneyRender");
	$col->rowspanByFields = array("RecordDate");
	$col->GroupHeader = "وضعیت بدهی در تاريخ " . $ComputeDateStr;
	$col->style = "DebitStyleRender";
	$col->align = "center";
	
	$col = $rpg->addColumn("تخفیف تعجیل", "debt_early","ReportMoneyRender");
	$col->rowspanByFields = array("RecordDate");
	$col->GroupHeader = "وضعیت بدهی در تاريخ " . $ComputeDateStr;
	$col->style = "DebitStyleRender";
	$col->align = "center";
	
	$col = $rpg->addColumn("کل", "debt_total","ReportMoneyRender");
	$col->GroupHeader = "وضعیت بدهی در تاريخ " . $ComputeDateStr;
	$col->style = "DebitStyleRender";
	$col->align = "center";
		
	$rpg->footerExplicit = true;
	$rpg->footerContent = "
		<tr>
			<td style='background-color:lightgreen' colspan=7 align=center rowspan=3>جمع تا تاریخ گزارش </td>
			<td>محاسبه شده</td>
			<td>".  number_format($totals["compute"]["debt_pure"])."</td>
			<td>".  number_format($totals["compute"]["debt_wage"])."</td>
			<td>".  number_format($totals["compute"]["debt_late"])."</td>		
			<td>".  number_format($totals["compute"]["debt_pnlt"])."</td>
			<td>".  number_format($totals["compute"]["debt_early"])."</td>
			<td>".  number_format($totals["compute"]["debt_total"])."</td>
		</tr>
		<tr>
			<td>پرداخت شده</td>
			<td>".  number_format($totals["payed"]["debt_pure"])."</td>
			<td>".  number_format($totals["payed"]["debt_wage"])."</td>
			<td>".  number_format($totals["payed"]["debt_late"])."</td>		
			<td>".  number_format($totals["payed"]["debt_pnlt"])."</td>
			<td>".  number_format($totals["payed"]["debt_early"])."</td>
			<td>".  number_format($totals["payed"]["debt_total"])."</td>
		</tr>
		<tr  style='background-color:lightgreen'>
			<td>مانده</td>
			<td>".  number_format($totals["remain"]["debt_pure"])."</td>
			<td>".  number_format($totals["remain"]["debt_wage"])."</td>
			<td>".  number_format($totals["remain"]["debt_late"])."</td>		
			<td>".  number_format($totals["remain"]["debt_pnlt"])."</td>
			<td>".  number_format($totals["remain"]["debt_early"])."</td>
			<td>".  number_format($totals["remain"]["debt_total"])."</td>
		</tr>
		<tr style='background-color:pink'>
			<td colspan=7 align=center>جمع تا انتهای قرارداد </td>
			<td>مانده</td>
			<td>".  number_format($totals["totalremain"]["debt_pure"])."</td>
			<td>".  number_format($totals["totalremain"]["debt_wage"])."</td>
			<td>".  number_format($totals["totalremain"]["debt_late"])."</td>		
			<td>".  number_format($totals["totalremain"]["debt_pnlt"])."</td>
			<td>".  number_format($totals["totalremain"]["debt_early"])."</td>
			<td>".  number_format($totals["totalremain"]["debt_total"])."</td>
		</tr>
	";	
	
	BeginReport();
	echo "<table style='border:2px groove #9BB1CD;border-collapse:collapse;width:100%'><tr>
			<td width=60px><img src='/framework/icons/logo.jpg' style='width:60px'></td>
			<td align='center' style='height:100px;vertical-align:middle;font-family:titr;font-size:15px'>
				گزارش پرداخت وام
			</td>
			<td width='200px' align='center' style='font-family:tahoma;font-size:11px'>تاریخ تهیه گزارش : " 
		. DateModules::shNow() . "<br>";
	
	echo "</td></tr></table>";
	
	//..........................................................
	$report2 = "";
	//..........................................................
	$rpg2 = new ReportGenerator();
	$rpg2->mysql_resource = $PureArr;

	$col = $rpg2->addColumn("تاریخ قسط", "InstallmentDate","ReportDateRender");
	$col = $rpg2->addColumn("مبلغ قسط", "InstallmentAmount","ReportMoneyRender");
	$col->EnableSummary();
	$col = $rpg2->addColumn("بهره قسط", "wage","ReportMoneyRender");
	$col->EnableSummary();
	$col = $rpg2->addColumn("اصل قسط", "pure","ReportMoneyRender");
	$col->EnableSummary();
	$col = $rpg2->addColumn("مانده اصل وام", "totalPure","ReportMoneyRender");
	ob_start();
	$rpg2->generateReport();
	$report2 = ob_get_clean();
	//..........................................................
	
	?>
	<table style="border:2px groove #9BB1CD;border-collapse:collapse;width:100%;font-family: nazanin;
		   font-size: 16px;line-height: 20px;">
		<tr>
			<td>شماره وام : <b><?= $ReqObj->RequestID ?></b></td>
			<td>وام گیرنده  :  <b><?= $ReqObj->_LoanPersonFullname  ?></b></td>
			<td>منبع   : <b><?= $ReqObj->_ReqPersonFullname ?></b></td>
			<td> تاریخ پرداخت وام :  <b><?= DateModules::miladi_to_shamsi($partObj->PartDate) ?></b></td>
		</tr>
		<tr>
			<td>مبلغ وام :  <b><?= number_format($partObj->PartAmount) ?> ریال				</b></td>
			<td>مدت تنفس :  <b><?= $partObj->DelayMonths  ?>ماه و  <?= $partObj->DelayDays ?> روز</b></td>
			<td> فاصله اقساط: <b><?= $partObj->PayInterval . ($partObj->IntervalType == "DAY" ? "روز" : "ماه") ?>
				</b></td>
			<td> تعداد اقساط: <b><?= $partObj->InstallmentCount ?></b></td>
		</tr>
		<tr>
			<td>  کارمزد وام :  <b><?= $partObj->CustomerWage ?> %</b></td>
			<td>کارمزد تاخیر : <b><?= $partObj->LatePercent ?> %</b></td>
			<td> درصد دیرکرد: <b><?= $partObj->ForfeitPercent ?> %</b></td>			
			<td></td>
		</tr>
	</table>	
<? $rpg->generateReport(); ?>


<script>
function showCommitmentData(el){
	el.style.display = "none";
	document.getElementById("CommitmentData").style.display = "block";
}
</script>
<center>
	<br>
	<a href="javascript:void(0)" onclick="showCommitmentData(this)">مشاهده جدول محاسبات تعهدی</a>
	<br>
</center>
	<?
	echo "<div id='CommitmentData' style=display:none>" . $report2 . "</div>";	
	die();
}
?>
<script>
LoanReport_payments.prototype = {
	TabID : '<?= $_REQUEST["ExtTabID"]?>',
	address_prefix : "<?= $js_prefix_address?>",

	get : function(elementID){
		return findChild(this.TabID, elementID);
	}
}

LoanReport_payments.prototype.showReport = function(btn, e)
{
	this.form = this.get("mainForm")
	this.form.target = "_blank";
	this.form.method = "POST";
	this.form.action =  this.address_prefix + "LoanPayment.php?show=true";
	this.form.submit();
	this.get("excel").value = "";
	return;
}

function LoanReport_payments()
{
	this.formPanel = new Ext.form.Panel({
		renderTo : this.get("main"),
		frame : true,
		layout :{
			type : "table",
			columns :1 
		},
		bodyStyle : "text-align:right;padding:5px",
		title : "گزارش پرداخت وام",
		defaults : {
			labelWidth :120
		},
		width : 650,
		items :[{
			xtype : "combo",
			store: new Ext.data.Store({
				proxy:{
					type: 'jsonp',
					url: this.address_prefix + '../request/request.data.php?task=SelectAllRequests2',
					reader: {root: 'rows',totalProperty: 'totalCount'}
				},
				fields :  ['loanFullname','PartAmount',"RequestID","PartDate", "ReqDate","RequestID",{
					name : "fullTitle",
					convert : function(value,record){
						return "[ " + record.data.RequestID + " ] " + 
							record.data.loanFullname + "  به مبلغ  " + 
							Ext.util.Format.Money(record.data.PartAmount) + " مورخ " + 
							MiladiToShamsi(record.data.PartDate);
					}
				}]				
			}),
			displayField: 'fullTitle',
			pageSize : 10,
			valueField : "RequestID",
			hiddenName : "RequestID",
			width : 600,
			tpl: new Ext.XTemplate(
				'<table cellspacing="0" width="100%"><tr class="x-grid-header-ct" style="height: 23px;">',
				'<td style="padding:7px">کد وام</td>',
				'<td style="padding:7px">وام گیرنده</td>',
				'<td style="padding:7px">مبلغ وام</td>',
				'<td style="padding:7px">تاریخ پرداخت</td> </tr>',
				'<tpl for=".">',
					'<tr class="x-boundlist-item" style="border-left:0;border-right:0">',
					'<td style="border-left:0;border-right:0" class="search-item">{RequestID}</td>',
					'<td style="border-left:0;border-right:0" class="search-item">{loanFullname}</td>',
					'<td style="border-left:0;border-right:0" class="search-item">',
						'{[Ext.util.Format.Money(values.PartAmount)]}</td>',
					'<td style="border-left:0;border-right:0" class="search-item">{[MiladiToShamsi(values.PartDate)]}</td> </tr>',
				'</tpl>',
				'</table>'
			),
			itemId : "RequestID"
		},{
			xtype : "shdatefield",
			name : "ComputeDate",
			fieldLabel : "محاسبه تا تاریخ"
		}],
		buttons : [{
			text : "مشاهده گزارش",
			handler : Ext.bind(this.showReport,this),
			iconCls : "report"
		}]
	});
}

LoanReport_paymentsObj = new LoanReport_payments();
</script>
<form id="mainForm">
	<center><br>
		<div id="main" ></div>
	</center>
	<input type="hidden" name="excel" id="excel">
</form>