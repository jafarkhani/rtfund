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

if(isset($_REQUEST["v"]))
{
	switch($_REQUEST["v"])
	{
		case "admin":
			sys_config::$db_server['database'] = "sajakrrt_oldcomputes";
			PdoDataAccess::$DB = null;
			break;
		
		case "oldsaja":
			sys_config::$db_server['database'] = "sajakrrt_rtfund3";
			PdoDataAccess::$DB = null; 
			break;
		
		case "saja":
			sys_config::$db_server['database'] = "sajakrrt_rtfund";
			PdoDataAccess::$DB = null;
			break;
	}
}

if(isset($_REQUEST["show"]))
{
	if(isset($_REQUEST["rtfund2"]))
	{
		sys_config::$db_server['database'] = "krrtfir_rtfund2";
		PdoDataAccess::$DB = null;
	}
	
	$RequestID = $_REQUEST["RequestID"];
	$ReqObj = new LON_requests($RequestID);
	$partObj = LON_ReqParts::GetValidPartObj($RequestID);
	//............ get total loan amount ......................
	$TotalAmount = LON_installments::GetTotalInstallmentsAmount($RequestID);
	//............ get remain untill now ......................
	$ComputeDate = !empty($_REQUEST["ComputeDate"]) ? $_REQUEST["ComputeDate"] : "";
	$ComputePenalty = !empty($_REQUEST["ComputePenalty"]) && $_REQUEST["ComputePenalty"] == "false" ? 
			false : true;
	$ComputeArr = LON_Computes::ComputePayments($RequestID, $ComputeDate, null, $ComputePenalty);
	$PureArr = LON_Computes::ComputePures($RequestID); 
	//if($_SESSION['USER']["UserName"] == "admin")
	//	print_r($ComputeArr);
	
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
	$rpg = new ReportGenerator();
		
	function RowColorRender($row){
		return $row["type"] == "pay" ? "#fcfcb6" : "";
	}
	$rpg->rowColorRender = "RowColorRender";
	
	
	function ActionRender($row, $value){
		if($value == "installment")
		{
			if($row["id"] == "0")
				return  $row["details"];
			return "قسط" ;
		}
		return "پرداخت " . $row["details"];
	}
	$rpg->addColumn("نوع عملیات", "type", "ActionRender");
		
	$rpg->addColumn("تاریخ عملیات", "RecordDate","ReportDateRender");

	$rpg->addColumn("مبلغ", "RecordAmount","ReportMoneyRender");
	
	$rpg->addColumn("اصل مبلغ", "pure","ReportMoneyRender");
	$rpg->addColumn("کارمزد", "wage","ReportMoneyRender");
	
	$rpg->addColumn("کارمزد تاخیر", "totallate","ReportMoneyRender");
	$rpg->addColumn("جریمه", "totalpnlt","ReportMoneyRender");
	
	function EarlyRender($row, $value){
		return $value;
		//return $row['type'] == "installment" ? 0 : $value;
	}
	$rpg->addColumn("تخفیف تعجیل", "totalearly","EarlyRender");
	
	function RemainsRender($row, $value){
		if($row["type"] == "pay")
			return 0;
		return number_format($value);
	}
	$col = $rpg->addColumn("مانده اصل", "remain_pure","RemainsRender");
	$col->EnableSummary();
	$col = $rpg->addColumn("مانده کارمزد", "remain_wage","RemainsRender");
	$col->EnableSummary();
	$col = $rpg->addColumn("مانده کارمزد تاخیر", "remain_late","RemainsRender");
	$col->EnableSummary();
	$col = $rpg->addColumn("مانده جریمه", "remain_pnlt","RemainsRender");
	$col->EnableSummary();
	$col = $rpg->addColumn("مانده تعجیل", "remain_early","RemainsRender");
	$col->EnableSummary();
	
	function totalRemainRender($row){
		if($row["type"] == "installment")
			return number_format($row["remain_pure"]*1 + $row["remain_wage"]*1 + 
					$row["remain_late"]*1 + $row["remain_pnlt"]*1 );
		else
			return number_format($row["remainPayAmount"]);
	}
	$col = $rpg->addColumn("مانده", "remain_pure","totalRemainRender");
	$col->EnableSummary(true);
	
	$rpg->mysql_resource = $ComputeArr;
	BeginReport();
	echo "<table style='border:2px groove #9BB1CD;border-collapse:collapse;width:100%'><tr>
			<td width=60px><img src='/framework/icons/logo.jpg' style='width:120px'></td>
			<td align='center' style='height:100px;vertical-align:middle;font-family:titr;font-size:15px'>
				گزارش پرداخت وام
			</td>
			<td width='200px' align='center' style='font-family:tahoma;font-size:11px'>تاریخ تهیه گزارش : " 
		. DateModules::shNow() . "<br>";
	
	echo "</td></tr></table>";
	
	?>
	<table style="border:2px groove #9BB1CD;border-collapse:collapse;width:100%;font-family: nazanin;
		   font-size: 16px;line-height: 20px;">
		<tr>
			<td>
				<table >
					<tr>
						<td>وام گیرنده :  </td>
						<td><b><?= $ReqObj->_LoanPersonFullname  ?></b></td>
					</tr>
					<tr>
						<td> تاریخ پرداخت وام:  </td>
						<td><b><?= DateModules::miladi_to_shamsi($partObj->PartDate) ?></b></td>
					</tr>
					<tr>
						<td>فاصله اقساط: </td>
						<td><b><?= $partObj->PayInterval . ($partObj->IntervalType == "DAY" ? "روز" : "ماه") ?>
							</b></td>
					</tr>
					<tr>
						<td> کارمزد وام:  </td>
						<td><b><?= $partObj->CustomerWage ?> %</b></td>
					</tr>
					<tr>
						<td>درصد دیرکرد: </td>
						<td><b><?= $partObj->ForfeitPercent ?> %
							</b></td>
					</tr>
				</table>
			</td>
			<td>
				<table>
					<tr>
						<td>شماره وام:</td>
						<td><b><?= $ReqObj->RequestID ?></b></td>
					</tr>
					<tr>
						<td>منبع  :</td>
						<td><b><?= $ReqObj->_ReqPersonFullname ?></b></td>
					</tr>
					<tr>
						<td>مدت تنفس :  </td>
						<td><b><?= $partObj->DelayMonths  ?>ماه و  <?= $partObj->DelayDays ?> روز</b></td>
					</tr>
					<tr>
						<td></td>
						<td><b></b></td>
					</tr>
					<tr>
						<td>کارمزد تاخیر :</td>
						<td><b><?= $partObj->LatePercent ?> %
							</b></td>
					</tr>
					<tr>
						<td>درصد بخشش : </td>
						<td><b><?= $partObj->ForgivePercent ?> %
							</b></td>
					</tr>
				</table>
			</td>
			<td>
				<table >
					<tr>
						<td>مبلغ وام :  </td>
						<td><b><?= number_format($partObj->PartAmount) ?> ریال
							</b></td>
					</tr>
					<tr>
						<td>جمع کل پرداختی تاکنون : </td>
						<td><b><?= number_format($totalPayed) ?> ریال
							</b></td>
					</tr>
					
					<? if(session::IsFramework()) {?>
					<tr>
						<td>مانده جریمه تاخیر: </td>
						<td><b><?= number_format($remains["remain_pnlt"]) ?> ریال							</b></td>
					</tr>
					<?}?>
				</table>
			</td>
			<td style="font-family: nazanin; font-size: 18px; font-weight: bold;line-height: 23px;">
				<table>
					<tr>
						<td>مانده بدهی تا امروز : </td>
						<td><b><?= $CurrentRemain ?></b></td>
					</tr>
					<tr>
						<td>مانده تا انتها : </td>
						<td><b><?= $TotalRemain?></b></td>
					</tr>
					<? if($ReqObj->ReqPersonID != SHEKOOFAI){ ?>
			 		<tr>
						<!--<td>مبلغ قابل پرداخت در صورت تسویه وام :</td>
						<td><b><?= $DefrayAmount ?></b></td>-->
						<td></td><td></td>
					</tr>
					<? } ?>
				</table>
			</td>
		</tr>
	</table>	
	<?
	$rpg->generateReport();
	
	if(!empty($_POST["commitment"]))
	{
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
		echo $rpg2->generateReport();
	}
	
	if(!empty($_POST["daily"]))
	{
		$StartDate = DateModules::AddToGDate($PureArr[0]["InstallmentDate"],1);
		$toDate = DateModules::Now();
		if($StartDate > $toDate)
		{
			echo "زمان شناسایی درآمدهای روزانه فرا نرسیده است";
			die();
		}
		$result = array();
		
		$ComputeDate = $StartDate;
		for($i=1; $i < count($PureArr);$i++)
		{
			if($ComputeDate > $toDate)
				break;
			$totalDays = DateModules::GDateMinusGDate($PureArr[$i]["InstallmentDate"],$ComputeDate);
			$wage = round(($PureArr[$i]["wage"]/$totalDays));
			$FundWage = round(($partObj->FundWage/$partObj->CustomerWage));
			$AgentWage = $wage - $FundWage;
			
			$startDay = $ComputeDate;
			$enDay = min($PureArr[$i]["InstallmentDate"], $toDate);
			$totalDays = DateModules::GDateMinusGDate($enDay,$startDay);
			$result[] = array(
				"fromdate" => DateModules::miladi_to_shamsi($startDay),
				"todate" => DateModules::miladi_to_shamsi($enDay),
				"wag" => $wage,
				"days" => $totalDays,
				"fundWage" => $FundWage,
				"AgentWage" => $AgentWage,
				"totalWage" =>$wage*$totalDays,
				"totalfundWage" => $FundWage*$totalDays,
				"totalAgentWage" => $AgentWage*$totalDays
			);
				
			$ComputeDate = DateModules::AddToGDate($PureArr[$i]["InstallmentDate"],1);;
		}
		
		$rpg3 = new ReportGenerator();
		$rpg3->mysql_resource = $result;

		$col = $rpg3->addColumn("از تاریخ", "fromdate","ReportDateRender");
		$col = $rpg3->addColumn("تا تاریخ", "todate","ReportDateRender");
		$col = $rpg3->addColumn("درآمد روزانه", "wag","ReportMoneyRender");
		$col = $rpg3->addColumn("تعداد روز", "days");
		
		$col = $rpg3->addColumn("کل درآمد", "totalWage","ReportMoneyRender");
		$col->EnableSummary();		
		$col = $rpg3->addColumn("سهم درآمد صندوق", "totalfundWage","ReportMoneyRender");
		$col->EnableSummary();
		$col = $rpg3->addColumn("سهم درآمد سرمایه گذار", "totalAgentWage","ReportMoneyRender");
		$col->EnableSummary();
		echo $rpg3->generateReport();
	}
	
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

LoanReport_payments.prototype.showReport2 = function(btn, e)
{
	this.form = this.get("mainForm")
	this.form.target = "_blank";
	this.form.method = "POST";
	this.form.action =  this.address_prefix + "DebitReport.php?show=true";
	this.form.submit();
	this.get("excel").value = "";
	return;
}

LoanReport_payments.prototype.showReport3 = function(btn, e)
{
	this.form = this.get("mainForm") 
	this.form.target = "_blank";
	this.form.method = "POST";
	this.form.action =  this.address_prefix + "LoanPayment.php?show=true&rtfund2=true";
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
		},{
			xtype : "container",
			html : "<input type=checkbox name=commitment > نمایش جدول محاسبات تعهدی"
		},{
			xtype : "container",
			html : "<input type=checkbox name=daily > نمایش جدول درآمد روزانه"
		}],
		buttons : [{
			text : "مشاهده گزارش",
			handler : Ext.bind(this.showReport,this),
			iconCls : "report"
		},{
			text : "گزارش پرداخت جدید",
			handler : Ext.bind(this.showReport2,this),
			iconCls : "report"
		},{
			text : "گزارش پرداخت  قبل از بروز رسانی مراحل پرداخت",
			handler : Ext.bind(this.showReport3,this),
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