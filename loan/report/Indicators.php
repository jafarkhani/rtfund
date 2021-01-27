<?php
//-----------------------------
//	Programmer	: SH.Jafarkhani
//	Date		: 98.02
//-----------------------------
ini_set("display_errors", "On");
require_once '../../header.inc.php';
require_once "../request/request.class.php";
require_once "../request/request.data.php";
require_once "ReportGenerator.class.php";

function GetData(){
	
	ini_set("memory_limit", "1000M");
	ini_set("max_execution_time", "600");
	
	$where = "";
	$whereParam = array();
			
	$query = "select p.*,
				r.EndDate,
				l.LoanDesc,
				r.RequestID,LoanPersonID,p1.mobile,
				concat_ws(' ',p1.fname,p1.lname,p1.CompanyName) LoanPersonName,
				concat_ws(' ',p2.fname,p2.lname,p2.CompanyName) ReqPersonName,
				BranchName,
				t_pay.SumPayments,
				t_pay.firstPay,
				t4.TotalInstallmentAmount
				
			from LON_requests r 
			left join BaseInfo bi on(bi.TypeID=5 AND bi.InfoID=StatusID)
			join LON_loans l using(LoanID)
			join BSC_persons p1 on(LoanPersonID=p1.PersonID)
			left join BSC_persons p2 on(ReqPersonID=p2.PersonID)
			join LON_ReqParts p on(p.RequestID=r.RequestID AND p.IsHistory='NO')
			join BSC_branches using(BranchID)
			left join (
				select RequestID,sum(InstallmentAmount) TotalInstallmentAmount 
				from LON_installments
				where  history='NO' AND IsDelayed='NO'
				group by RequestID			
			)t4 on(r.RequestID=t4.RequestID)
			
			left join (
				select RequestID,sum(PayAmount) SumPayments, min(PayDate) firstPay
				from LON_payments p
				group by RequestID			
			)t_pay on(r.RequestID=t_pay.RequestID)

			where r.StatusID=" . LON_REQ_STATUS_CONFIRM . " " . $where . "
		
			group by r.RequestID
			order by r.RequestID,p.PartID";
	
	$dt = PdoDataAccess::runquery_fetchMode($query, $whereParam);
	if($_SESSION["USER"]["UserName"] == "admin")
	{
		//print_r(ExceptionHandler::PopAllExceptions()); 
		//echo PdoDataAccess::GetLatestQueryString();
	}
	$ComputeDate = !empty($_POST["ComputeDate"]) ? 
			DateModules::shamsi_to_miladi($_POST["ComputeDate"],"-") : DateModules::now();
	
	$result = array();
	
	$totalPayed = 0;
	$totalRemain = 0;
	$totalDelayed = 0;
	$BadNPL = 0;
	$BadPercent = 35;
	while($row = $dt->fetch())
	{
		$computeArr = LON_Computes::ComputePayments($row["RequestID"], $ComputeDate);
		$remain = LON_Computes::GetCurrentRemainAmount($row["RequestID"],$computeArr, $ComputeDate);
		$TRemain = LON_Computes::GetTotalRemainAmount($row["RequestID"],$computeArr);
		
		$totalRemain += $TRemain;
		$totalDelayed += $remain;
		$totalPayed += $row["SumPayments"];
		
		$row["TotalRemainder"] = $TRemain;
		$row["CurrentRemainder"] = $remain;
		$row["totalDebit"] = $row["TotalInstallmentAmount"]*1;
		
		$sum = 0;
		$debtClass = LON_Computes::GetDebtClassificationInfo($row["RequestID"], $computeArr, $ComputeDate);
		if($debtClass){
			$row["DebitClassify"] = $debtClass["title"];
		
			$row["IsDelayedInDebitClass"] = false;
			if(	$debtClass["classes"]["2"]["amount"]*1 > $debtClass["FollowAmount2"] ||
				$debtClass["classes"]["3"]["amount"]*1 > $debtClass["FollowAmount3"] ||
				$debtClass["classes"]["4"]["amount"]*1 > $debtClass["FollowAmount4"])
				$row["IsDelayedInDebitClass"] = true;

			
			foreach($debtClass["classes"] as $record){
				$row[ "Debit_" . $record["code"] ] = $record["amount"];
				$sum += $record["amount"];
			}
		}
		else{
			$row["DebitClassify"] = "";
			$row[ "Debit_1"] = 0;
			$row[ "Debit_2"] = 0;
			$row[ "Debit_3"] = 0;
			$row[ "Debit_4"] = 0;
			$sum = 0;
		}
		
		$row["NPL"] = round($sum*100/$row["totalDebit"], 2) . "%";
		
		if($row["NPL"] > $BadPercent){
			$BadNPL += $row["SumPayments"];
			$row["IsBadNPL"] = "1";
		}
		else{
			$row["IsBadNPL"] = "0";
		}
		
		$result[] = $row;
	}
	
	return array(
		"results" => $result,
		"totalPayed" => $totalPayed,
		"BadPercent" => $BadPercent,
		"BadNPL" => $BadNPL,
		"totalDelayed" => $totalDelayed,
		"totalRemain" => $totalRemain,
		"CR" => round($BadNPL*100/$totalPayed, 2),
		"CR2" => round($totalDelayed*100/$totalRemain)
	);
}	
	
function ListData($IsDashboard = false){
	
	$computes = GetData();
	
	$rpt = new ReportGenerator();
	$rpt->excel = !empty($_POST["excel"]);
	$rpt->mysql_resource = $computes["results"];
	
	function LoanReportRender($row,$value){
		return "<a href=LoanPayment.php?show=tru&RequestID=" . $value . " target=blank >" . $value . "</a>";
	}

	$col = $rpt->addColumn("شماره وام", "RequestID", "LoanReportRender");
	$col->ExcelRender = false;
	$rpt->addColumn("شعبه وام", "BranchName");
	$rpt->addColumn("نوع وام", "LoanDesc");
	$rpt->addColumn("معرف", "ReqPersonName");
	$rpt->addColumn("وام گیرنده", "LoanPersonName");
	$rpt->addColumn("مبلغ پرداختی وام", "PartAmount", "ReportMoneyRender");
	
	/*$rpt->addColumn("تعداد اقساط", "InstallmentCount");
	$rpt->addColumn("کارمزد مشتری", "CustomerWage");
	$rpt->addColumn("کارمزد صندوق", "FundWage");
	$rpt->addColumn("درصد دیرکرد", "ForfeitPercent");*/
	
	/*$rpt->addColumn("سررسید اولین قسط", "FirstInstallmentDate","ReportDateRender");
	$rpt->addColumn("سررسید آخرین قسط", "LastInstallmentDate","ReportDateRender");
	$rpt->addColumn("مبلغ قسط", "InstallmentAmount","ReportMoneyRender");
	$rpt->addColumn("تاریخ اولین مرحله پرداخت وام", "firstPay","ReportDateRender");
	$rpt->addColumn("تاریخ آخرین پرداخت مشتری", "MaxPayDate","ReportDateRender");
	$rpt->addColumn("آخرین وضعیت پیگیری", "LatestFollowStatus");*/
	
	$col = $rpt->addColumn("جمع کل پرداخت وام", "SumPayments", "ReportMoneyRender");
	$col->ExcelRender = false;
	$col->EnableSummary();
	
	$col = $rpt->addColumn("جمع کل مطالبات", "totalDebit", "ReportMoneyRender");
	$col->ExcelRender = false;
	$col->EnableSummary();
	
	function TotalRemainderRender($row,$value){
		return "<a href=LoanPayment.php?show=tru&RequestID=" . $row["RequestID"] . 
				" target=blank >" . number_format($value) . "</a>";
	}
	$col = $rpt->addColumn("مانده کل تا انتها", "TotalRemainder","TotalRemainderRender");	
	$col->ExcelRender =false;
	$col->EnableSummary();
	
	$col = $rpt->addColumn("مانده سررسید شده", "CurrentRemainder","ReportMoneyRender");	
	$col->EnableSummary();
		
	$rpt->addColumn("نوع بدهی", "DebitClassify");	
	
	/*$col = $rpt->addColumn("حقوقی شده", "IsDelayedInDebitClass","IsDelayedInDebitClassRender");
	$col->align = "center";
	function IsDelayedInDebitClassRender($row,$value){
		return ($value) ? "بلی" : "";
	}*/
	
	function ColorRender($row){
		return $row["IsBadNPL"] == "1" ? "#fdffbd" : "";
	}
	$rpt->rowColorRender = "ColorRender";
	
	$dt = PdoDataAccess::runquery("select InfoID,param4,InfoDesc from BaseInfo b1 
		where b1.TypeID=" . TYPEID_DebitClass . " group by param4");
	foreach($dt as $row)
	{
		$col = $rpt->addColumn("C" . $row["InfoID"], "Debit_" . $row["param4"],"ReportMoneyRender");
		$col->EnableSummary();
		$col->align = "center";
	} 
	$rpt->addColumn("NPL", "NPL");	
	
	if(!$rpt->excel && !$IsDashboard)
	{
		BeginReport();
		echo "<table style='border:2px groove #9BB1CD;border-collapse:collapse;width:100%'><tr>
				<td width=60px><img src='/framework/icons/logo.jpg' style='width:120px'></td>
				<td align='center' style='height:100px;vertical-align:middle;font-family: titr;font-size:15px'>
					گزارش محاسبه شاخص های وام
				</td>
				<td width='200px' align='center' style='font-family:tahoma;font-size:11px'>تاریخ تهیه گزارش : " 
			. DateModules::shNow() . "<br>";
		if(!empty($_POST["fromReqDate"]))
		{
			echo "<br>تاریخ محاسبه : " . 
				$_POST["ComputeDate"]
					? $_POST["ComputeDate"]
					: DateModules::shNow();
		}
		echo "</td></tr></table>";
		?>
<style>.blueText{
	font-weight: bold;
	color: #0D6EB2;
}</style>
		<table cellpadding="4px" style="border:2px groove #9BB1CD;border-collapse:collapse;width:100%;font-family: nazanin;
			   font-size: 16px;line-height: 20px;">
			<tr>
				<td>ریسک اعتباری صندوق (CR) : <?= $computes["CR"] ?> %</td>
				<td>جمع کل وام های پرداخت شده :</td>
				<td class="blueText"><?= number_format($computes["totalPayed"])?></td>
				<td>جمع کل وام های نامطلوب :</td>
				<td class="blueText"><?= number_format($computes["BadNPL"])?></td>
			</tr>
			<tr>
				<td>شاخص معوقات صندوق در هر روز : <?= $computes["CR2"] ?> %</td>
				<td>مجموع کل مطالبات :</td>
				<td class="blueText"><?= number_format($computes["totalRemain"])?></td>
				<td>مجموع مطالبات حال شده :</td>
				<td class="blueText"><?= number_format($computes["totalDelayed"])?></td>
			</tr>
		</table>
		<?
	}
	$rpt->generateReport();
	
	die();
}

if(isset($_REQUEST["show"]))
{	
	ListData();
}

if(isset($_REQUEST["rpcmp_chart"]))
{
	$page_rpg->mysql_resource = GetData();
	$page_rpg->GenerateChart();
	die();
}
if(isset($_REQUEST["dashboard_show"]))
{
	$chart = ReportGenerator::DashboardSetParams($_REQUEST["rpcmp_ReportID"]);
	if(!$chart)
		ListDate(true);	
	
	$page_rpg->mysql_resource = GetData();
	$page_rpg->GenerateChart(false, $_REQUEST["rpcmp_ReportID"]);
	die();	
}
?>
<script>
LoanReport_indicators.prototype = {
	TabID : '<?= $_REQUEST["ExtTabID"]?>',
	address_prefix : "<?= $js_prefix_address?>",

	get : function(elementID){
		return findChild(this.TabID, elementID);
	}
}

LoanReport_indicators.prototype.showReport = function(btn, e)
{
	this.form = this.get("mainForm")
	this.form.target = "_blank";
	this.form.method = "POST";
	this.form.action =  this.address_prefix + "Indicators.php?show=true";
	this.form.submit();
	this.get("excel").value = "";
	return;
}

function LoanReport_indicators()
{		
	this.formPanel = new Ext.form.Panel({
		renderTo : this.get("main"),
		frame : true,
		layout :{
			type : "table",
			columns :2
		},
		bodyStyle : "text-align:right;padding:5px",
		title : "گزارش شاخص های وام",
		width : 780,
		items :[{
			xtype : "shdatefield",
			name : "ComputeDate",
			labelWidth : 120,
			colspan: 2,
			fieldLabel : "محاسبه تا تاریخ"
		}],
		buttons : [{
			text : "مشاهده گزارش",
			handler : Ext.bind(this.showReport,this),
			iconCls : "report"
		}]
	});
	
	this.formPanel.getEl().addKeyListener(Ext.EventObject.ENTER, function(keynumber,e){
		
		LoanReport_indicatorsObj.showReport();
		e.preventDefault();
		e.stopEvent();
		return false;
	});
}

LoanReport_indicatorsObj = new LoanReport_indicators();
</script>
<form id="mainForm">
	<center><br>
		<div id="main" ></div>
	</center>
	<input type="hidden" name="excel" id="excel">
</form>
