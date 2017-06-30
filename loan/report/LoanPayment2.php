<?php
//-----------------------------
//	Programmer	: SH.Jafarkhani
//	Date		: 94.12
//-----------------------------

require_once '../header.inc.php';
require_once "ReportGenerator.class.php";
require_once '../request/request.class.php';
require_once '../request/request.data.php';

if(isset($_REQUEST["show"]))
{
	$RequestID = $_REQUEST["RequestID"];
	$ReqObj = new LON_requests($RequestID);
	$PartObj = LON_ReqParts::GetValidPartObj($RequestID);
	$arr = ComputeWagesAndDelays($PartObj, $PartObj->PartAmount, $PartObj->PartDate, $PartObj->PartDate);
	$WageAmount = $arr["TotalCustomerWage"];
	//............ get remain untill now ......................
	$dt = array();
	$ComputeArr = LON_requests::ComputePayments2($RequestID, $dt);
	$PureArr = LON_requests::ComputePures($RequestID);
	//............ get remain untill now ......................
	$CurrentRemain = LON_requests::GetCurrentRemainAmount($RequestID, $ComputeArr);
	$TotalRemain = LON_requests::GetTotalRemainAmount($RequestID, $ComputeArr);
	$DefrayAmount = LON_requests::GetDefrayAmount($RequestID, $ComputeArr, $PureArr);
	//.........................................................
	
	$rpg = new ReportGenerator();
		
	function RowColorRender($row){
		return $row["ActionType"] == "pay" ? "#fcfcb6" : "";
	}
	$rpg->rowColorRender = "RowColorRender";
	
	
	function ActionRender($row, $value){
		if($value == "installment")
			return "قسط" ;
		if($row["ActionAmount"]*1 < 0)
			return "هزینه";
		return "پرداخت";
	}
	$rpg->addColumn("نوع عملیات", "ActionType", "ActionRender");
		
	$rpg->addColumn("تاریخ عملیات", "ActionDate","ReportDateRender");

	$rpg->addColumn("مبلغ", "ActionAmount","ReportMoneyRender");
	
	$rpg->addColumn("تعداد روز تاخیر", "ForfeitDays");
	$rpg->addColumn("مبلغ تاخیر", "CurForfeitAmount","ReportMoneyRender");
	
	$rpg->addColumn("تاخیر کل", "ForfeitAmount","ReportMoneyRender");
	
	$rpg->addColumn("مانده کل", "TotalRemainder","ReportMoneyRender");
	
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
	
	$ReqObj = new LON_requests($RequestID);
	$partObj = LON_ReqParts::GetValidPartObj($RequestID);
	
	//..........................................................
	$report2 = "";
	if($ReqObj->ReqPersonID != SHEKOOFAI)
	{
		//..........................................................
		$rpg2 = new ReportGenerator();
		$rpg2->mysql_resource = $PureArr;

		$col = $rpg2->addColumn("تاریخ قسط", "InstallmentDate","ReportDateRender");
		$col = $rpg2->addColumn("مبلغ قسط", "InstallmentAmount","ReportMoneyRender");
		$col = $rpg2->addColumn("بهره قسط", "profit","ReportMoneyRender");
		$col = $rpg2->addColumn("بهره قسط (تجمعي)", "SumProfit","ReportMoneyRender");
		$col = $rpg2->addColumn("اصل قسط", "pureAmount","ReportMoneyRender");
		$col = $rpg2->addColumn("مانده اصل وام", "pureRemain","ReportMoneyRender");
		ob_start();
		$rpg2->generateReport();
		$report2 = ob_get_clean();
		//..........................................................
	}
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
				</table>
			</td>
			<td>
				<table >
					<tr>
						<td>مدت تنفس :  </td>
						<td><b><?= $partObj->DelayMonths  ?></b></td>
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
				<table >
					<tr>
						<td>مبلغ وام :  </td>
						<td><b><?= number_format($partObj->PartAmount) ?> ریال
							</b></td>
					</tr>
					<tr>
						<td>مبلغ کارمزد : </td>
						<td><b><?= number_format($WageAmount)?> ریال
							</b></td>
					</tr>
					<tr>
						<td>جمع وام و کارمزد : </td>
						<td><b><?= number_format($partObj->PartAmount + $WageAmount) ?> ریال
							</b></td>
					</tr>
				</table>
			</td>
			<td style="font-family: nazanin; font-size: 18px; font-weight: bold;line-height: 23px;">
				<table>
					<tr>
						<td>مانده قابل پرداخت معوقه : </td>
						<td><b><?= number_format($CurrentRemain)?> ریال
							</b></td>
					</tr>
					<tr>
						<td>مانده تا انتها : </td>
						<td><b><?= number_format($TotalRemain)?> ریال
							</b></td>
					</tr>
					<? if($ReqObj->ReqPersonID != SHEKOOFAI){ ?>
					<tr>
						<td>مبلغ قابل پرداخت در صورت تسویه وام :</td>
						<td><b><?= number_format($DefrayAmount) ?> ریال
							</b></td>
					</tr>
					<? } ?>
				</table>
			</td>
		</tr>
	</table>	
	<?
	
	$rpg->generateReport();
	
	echo "<br>" . $report2;	
	
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
	this.form.action =  this.address_prefix + "LoanPayment2.php?show=true";
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
			columns :2
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