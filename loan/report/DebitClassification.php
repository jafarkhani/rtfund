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

function ReqPersonRender($row,$value){
	return $value == "" ? "منابع داخلی" : $value;
}
function intervslRender($row, $value){
	return $value . ($row["IntervalType"] == "DAY" ? " روز" : " ماه");
}
	
$page_rpg = new ReportGenerator("mainForm","LoanReport_DebitClassifyObj");

$page_rpg->addColumn("شماره وام", "RequestID");
$page_rpg->addColumn("شعبه وام", "BranchName");
$page_rpg->addColumn("نوع وام", "LoanDesc");
$page_rpg->addColumn("تضامین", "tazamin");
$page_rpg->addColumn("معرف", "ReqPersonName");
$page_rpg->addColumn("وام گیرنده", "LoanPersonName");
$page_rpg->addColumn("موبایل", "mobile");
$page_rpg->addColumn("وضعیت", "StatusDesc");
$col = $page_rpg->addColumn("تاریخ خاتمه", "EndingDate");
$col->type = "date";
$page_rpg->addColumn("مبلغ وام", "PartAmount");
$page_rpg->addColumn("جمع وام و کارمزد", "TotalLoanAmount");
$page_rpg->addColumn("شرح", "PartDesc");
$page_rpg->addColumn("ماه تنفس", "DelayMonths");
$page_rpg->addColumn("روز تنفس", "DelayDays");
$page_rpg->addColumn("فاصله اقساط", "PayInterval", "intervslRender");
$page_rpg->addColumn("تعداد اقساط", "InstallmentCount");
$page_rpg->addColumn("کارمزد مشتری", "CustomerWage");
$page_rpg->addColumn("کارمزد صندوق", "FundWage");
$page_rpg->addColumn("درصد دیرکرد", "ForfeitPercent");

$col = $page_rpg->addColumn("سررسید اولین قسط", "FirstInstallmentDate"); $col->type = "date";
$col = $page_rpg->addColumn("سررسید آخرین قسط", "LastInstallmentDate"); $col->type = "date";
$col = $page_rpg->addColumn("مبلغ قسط", "InstallmentAmount","ReportMoneyRender");
$col = $page_rpg->addColumn("تاریخ آخرین پرداخت مشتری", "MaxPayDate"); $col->type = "date";
$col = $page_rpg->addColumn("جمع کل پرداختی تاکنون", "TotalPayAmount", "ReportMoneyRender");
$col = $page_rpg->addColumn("تعداد اقساط معوق", "delayedInstallmentsCount"); $col->IsQueryField = false;

$col = $page_rpg->addColumn("مانده کل تا انتها", "TotalRemainder","ReportMoneyRender");	 $col->IsQueryField = false;
$col = $page_rpg->addColumn("مانده تا انتها بدون احتساب جریمه دیرکرد", "TotalNonPenaltyRemainder","ReportMoneyRender");	 $col->IsQueryField = false;
$col = $page_rpg->addColumn("طبقه وام", "LoanLevel"); $col->IsQueryField = false;
$col = $page_rpg->addColumn("آخرین وضعیت پیگیری", "LatestFollowStatus");
$col = $page_rpg->addColumn("تاریخ آخرین وضعیت پیگیری", "RegDate"); $col->type = "date";

$col = $page_rpg->addColumn("مانده قابل پرداخت معوقه", "CurrentRemainder","ReportMoneyRender");	$col->IsQueryField = false;
$col = $page_rpg->addColumn("مانده اصل وام تا انتها", "remain_pure","ReportMoneyRender"); $col->IsQueryField = false;
$col = $page_rpg->addColumn("کارمزد معوقه", "remain_wage","ReportMoneyRender"); $col->IsQueryField = false;
$col = $page_rpg->addColumn("مانده اصل و کارمزد", "remain_loan","ReportMoneyRender"); $col->IsQueryField = false;
$col = $page_rpg->addColumn("کارمزد تاخیر معوقه", "remain_late","ReportMoneyRender"); $col->IsQueryField = false;
$col = $page_rpg->addColumn("جریمه معوقه", "remain_pnlt","ReportMoneyRender"); $col->IsQueryField = false;
	

function MakeWhere(&$where, &$whereParam){

	if(session::IsPortal() && isset($_REQUEST["dashboard_show"]))
	{
		if($_REQUEST["DashboardType"] == "shareholder" || $_REQUEST["DashboardType"] == "agent")
			$where .= " AND ReqPersonID=" . $_SESSION["USER"]["PersonID"];
		if($_REQUEST["DashboardType"] == "customer")
			$where .= " AND LoanPersonID=" . $_SESSION["USER"]["PersonID"];
	}
	
	foreach($_POST as $key => $value)
	{
		if($key == "excel" || $key == "OrderBy" || $key == "OrderByDirection" || 
				$value === "" || strpos($key, "combobox") !== false || strpos($key, "rpcmp") !== false ||
				strpos($key, "reportcolumn_fld") !== false || strpos($key, "reportcolumn_ord") !== false)
			continue;

		if($key == "ForfeitDays" || $key == "ComputeDate" || $key == "RemainPercent" || $key == "FollowLevelID")
			continue;
		
		$prefix = "";
		switch($key)
		{
			case "fromRequestID":
			case "toRequestID":
				$prefix = "r.";
				break;
			case "fromInstallmentDate":
			case "toInstallmentDate":
				$value = DateModules::shamsi_to_miladi($value, "-");
				break;
			case "fromInstallmentAmount":
			case "toInstallmentAmount":
				$value = preg_replace('/,/', "", $value);
				break;
		}
		if(strpos($key, "from") === 0)
			$where .= " AND " . $prefix . substr($key,4) . " >= :$key";
		else if(strpos($key, "to") === 0)
			$where .= " AND " . $prefix . substr($key,2) . " <= :$key";
		else
			$where .= " AND " . $prefix . $key . " = :$key";
		$whereParam[":$key"] = $value;
	}
}	

function GetData(){
	
	ini_set("memory_limit", "1000M");
	ini_set("max_execution_time", "600");
	
	$where = "";
	$whereParam = array();
	$userFields = ReportGenerator::UserDefinedFields();
	MakeWhere($where, $whereParam);
			
	$query = "select p.*,
				r.EndingDate,
				l.LoanDesc,
				r.RequestID,LoanPersonID,p1.mobile,
				concat_ws(' ',p1.fname,p1.lname,p1.CompanyName) LoanPersonName,
				concat_ws(' ',p2.fname,p2.lname,p2.CompanyName) ReqPersonName,
				BranchName,
				bi.InfoDesc StatusDesc,
				bif.InfoDesc LatestFollowStatus,
				t5.RegDate,
				tazamin,
				t1.InstallmentAmount,
				t1.LastInstallmentDate,
				t1.FirstInstallmentDate,
				t4.TotalInstallmentAmount,
				t3.TotalPayAmount,
				t3.MaxPayDate" .
				($userFields != "" ? "," . $userFields : "")."
				
			from LON_requests r 
			left join BaseInfo bi on(bi.TypeID=5 AND bi.InfoID=StatusID)
			join LON_loans l using(LoanID)
			join BSC_persons p1 on(LoanPersonID=p1.PersonID)
			left join BSC_persons p2 on(ReqPersonID=p2.PersonID)
			join LON_ReqParts p on(p.RequestID=r.RequestID AND p.IsHistory='NO')
			join BSC_branches using(BranchID)
			left join (
				select RequestID,max(StatusID) FollowStatusID,RegDate from LON_follows group by RequestID
			)t5 on(r.RequestID=t5.RequestID)
			left join BaseInfo bif on(bif.TypeID=98 AND bif.InfoID=t5.FollowStatusID)
			left join (
				select RequestID,InstallmentAmount, max(InstallmentDate) LastInstallmentDate , min(InstallmentDate) FirstInstallmentDate
				from LON_installments
				where history='NO' AND IsDelayed='NO'
				group by RequestID
			)t1 on(r.RequestID=t1.RequestID)
			left join (
				select ObjectID,group_concat(title,' به شماره سريال ',num, ' و مبلغ ', 
					format(amount,2) separator '<br>') tazamin
				from (	
					select ObjectID,InfoDesc title,group_concat(if(KeyTitle='no',paramValue,'') separator '') num,
					group_concat(if(KeyTitle='amount',paramValue,'') separator '') amount
					from DMS_documents d
					join BaseInfo b1 on(InfoID=d.DocType AND TypeID=8)
					join DMS_DocParamValues dv  using(DocumentID)
					join DMS_DocParams using(ParamID)
				    where ObjectType='loan' AND b1.param1=1
					group by ObjectID, DocumentID
				)t
				group by ObjectID
			)t2 on(t2.ObjectID=r.RequestID)
			
			left join (
				select RequestID,sum(PayAmount) TotalPayAmount , max(PayDate) MaxPayDate
				from LON_BackPays
				left join ACC_IncomeCheques i using(IncomeChequeID)
				where if(PayType=" . BACKPAY_PAYTYPE_CHEQUE . ",ChequeStatus=".INCOMECHEQUE_VOSUL.",1=1)
				group by RequestID			
			)t3 on(r.RequestID=t3.RequestID)
			
			left join (
				select RequestID,sum(InstallmentAmount) TotalInstallmentAmount 
				from LON_installments
				where  history='NO' AND IsDelayed='NO'
				group by RequestID			
			)t4 on(r.RequestID=t4.RequestID)
			
			where 1=1 " . $where . "
		
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
	
	$DebitClassify = array();
	$temp = PdoDataAccess::runquery("select * from BaseInfo where typeID=" . TYPEID_DebitType);
	foreach($temp as $row)
	{
		$DebitClassify[ $row["InfoID"] ] = array(
			"min" => $row["param1"],
			"max" => $row["param2"],
			"classes" => array()
		);
	}
	$temp = PdoDataAccess::runquery("select * from BaseInfo where typeID=" . TYPEID_DebitClass);
	foreach($temp as $row)
	{
		$DebitClassify[ $row["param1"] ]["classes"][] = array(
			"id" => $row["InfoID"],
			"minDay" => $row["param2"],
			"maxDay" => $row["param3"]
		);
	}
	$result = array();
	while($row = $dt->fetch())
	{
		$computeArr = LON_Computes::ComputePayments($row["RequestID"], $ComputeDate);
		$remain = LON_Computes::GetCurrentRemainAmount($row["RequestID"],$computeArr, $ComputeDate);
		$RemainArr = LON_Computes::GetRemainAmounts($row["RequestID"],$computeArr, $ComputeDate);
		$totalRemain = LON_Computes::GetTotalRemainAmount($row["RequestID"],$computeArr);

		if($remain == 0)
			continue;
		
		$row["TotalRemainder"] = $totalRemain;
		$row["CurrentRemainder"] = $remain;
		
		// initial all classes
		foreach($DebitClassify as $record)
			foreach($record["classes"] as $r2)
				$row[ "Debit_" . $r2["id"] ] = 0;
		
		// find the related class group
		$ClassArr = array();
		foreach($DebitClassify as $record)
		{
			if($record["min"] <= $totalRemain && $totalRemain <= $record["max"])
			{
				$ClassArr = $record["classes"];
				break;
			}
		}
		
		// fill the related class
		foreach($computeArr as $crecord)
		{
			if($crecord["type"] != "installment" || $crecord["id"]*1 == 0)
				continue;
			
			$totalRemain = $crecord["remain_pure"] + $crecord["remain_wage"] + $crecord["remain_pnlt"] + $crecord["remain_late"];
			$diffDays = DateModules::GDateMinusGDate($ComputeDate,$crecord["RecordDate"]);
			foreach($ClassArr as $cr)
				if($diffDays >= $cr["minDay"] && $diffDays <= $cr["maxDay"])
				{
					$row[ "Debit_" . $cr["id"] ] += $totalRemain;
					break;
				}
		}
		
		$result[] = $row;
	}
	
	return $result;
}	
	
function ListData($IsDashboard = false){
	
	$rpt = new ReportGenerator();
	$rpt->excel = !empty($_POST["excel"]);
	$rpt->mysql_resource = GetData();
	
	function LoanReportRender($row,$value){
		return "<a href=LoanPayment.php?show=tru&RequestID=" . $value . " target=blank >" . $value . "</a>";
	}

	$col = $rpt->addColumn("شماره وام", "RequestID", "LoanReportRender");
	$col->ExcelRender = false;
	$rpt->addColumn("شعبه وام", "BranchName");
	$rpt->addColumn("نوع وام", "LoanDesc");
	$rpt->addColumn("تضامین", "tazamin");
	$rpt->addColumn("معرف", "ReqPersonName");
	$rpt->addColumn("وام گیرنده", "LoanPersonName");
	$rpt->addColumn("موبایل", "mobile");
	$rpt->addColumn("وضعیت", "StatusDesc");
	$rpt->addColumn("تاریخ خاتمه", "EndingDate", "ReportDateRender");
	
	$rpt->addColumn("مبلغ وام", "PartAmount", "ReportMoneyRender");
	$rpt->addColumn("شرح", "PartDesc");
	$rpt->addColumn("ماه تنفس", "DelayMonths");
	$rpt->addColumn("روز تنفس", "DelayDays");
	$rpt->addColumn("فاصله اقساط", "PayInterval", "intervslRender");
	$rpt->addColumn("تعداد اقساط", "InstallmentCount");
	$rpt->addColumn("کارمزد مشتری", "CustomerWage");
	$rpt->addColumn("کارمزد صندوق", "FundWage");
	$rpt->addColumn("درصد دیرکرد", "ForfeitPercent");
	
	$rpt->addColumn("سررسید اولین قسط", "FirstInstallmentDate","ReportDateRender");
	$rpt->addColumn("سررسید آخرین قسط", "LastInstallmentDate","ReportDateRender");
	$rpt->addColumn("مبلغ قسط", "InstallmentAmount","ReportMoneyRender");
	$rpt->addColumn("تاریخ آخرین پرداخت مشتری", "MaxPayDate","ReportDateRender");
	
	$col = $rpt->addColumn("جمع کل پرداختی تاکنون", "TotalPayAmount", "ReportMoneyRender");
	$col->ExcelRender = false;
	$col->EnableSummary();
	
	function TotalRemainderRender($row,$value){
		return "<a href=LoanPayment.php?show=tru&RequestID=" . $row["RequestID"] . 
				" target=blank >" . number_format($value) . "</a>";
	}
	$col = $rpt->addColumn("مانده کل تا انتها", "TotalRemainder","TotalRemainderRender");	
	$col->EnableSummary();
	
	$col = $rpt->addColumn("مانده قابل پرداخت معوقه", "CurrentRemainder","ReportMoneyRender");	
	$col->EnableSummary();
		
	$dt = PdoDataAccess::runquery("select b1.*,b2.InfoDesc as GroupDesc from BaseInfo b1 
		join BaseInfo b2 on(b1.param1=b2.InfoID and b2.TypeID=" . TYPEID_DebitType . ") 
		where b1.TypeID=" . TYPEID_DebitClass);
	foreach($dt as $row)
	{
		$col = $rpt->addColumn($row["InfoDesc"], "Debit_" . $row["InfoID"],"ReportMoneyRender");
		$col->GroupHeader = $row["GroupDesc"];
		$col->EnableSummary();
	}
	
	if(!$rpt->excel && !$IsDashboard)
	{
		BeginReport();
		echo "<table style='border:2px groove #9BB1CD;border-collapse:collapse;width:100%'><tr>
				<td width=60px><img src='/framework/icons/logo.jpg' style='width:120px'></td>
				<td align='center' style='height:100px;vertical-align:middle;font-family: titr;font-size:15px'>
					گزارش محاسبه و طبقه بندی مطالبات
				</td>
				<td width='200px' align='center' style='font-family:tahoma;font-size:11px'>تاریخ تهیه گزارش : " 
			. DateModules::shNow() . "<br>";
		if(!empty($_POST["fromReqDate"]))
		{
			echo "<br>گزارش از تاریخ : " . $_POST["fromReqDate"] . 
				($_POST["toReqDate"] != "" ? " - " . $_POST["toReqDate"] : "");
		}
		echo "</td></tr></table>";
	}
	if($IsDashboard)
	{
		echo "<div style=direction:rtl;padding-right:10px>";
		$rpt->generateReport();
		echo "</div>";
	}
	else
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
LoanReport_DebitClassify.prototype = {
	TabID : '<?= $_REQUEST["ExtTabID"]?>',
	address_prefix : "<?= $js_prefix_address?>",

	get : function(elementID){
		return findChild(this.TabID, elementID);
	}
}

LoanReport_DebitClassify.prototype.showReport = function(btn, e)
{
	this.form = this.get("mainForm")
	this.form.target = "_blank";
	this.form.method = "POST";
	this.form.action =  this.address_prefix + "DebitClassification.php?show=true";
	this.form.submit();
	this.get("excel").value = "";
	return;
}

function LoanReport_DebitClassify()
{		
	this.formPanel = new Ext.form.Panel({
		renderTo : this.get("main"),
		frame : true,
		layout :{
			type : "table",
			columns :2
		},
		bodyStyle : "text-align:right;padding:5px",
		title : "گزارش محاسبه و طبقه بندی مطالبات",
		width : 780,
		items :[{
			xtype : "combo",
			store : new Ext.data.SimpleStore({
				proxy: {
					type: 'jsonp',
					url: this.address_prefix + '../../framework/person/persons.data.php?' +
						"task=selectPersons&UserTypes=IsAgent,IsSupporter&EmptyRow=true",
					reader: {root: 'rows',totalProperty: 'totalCount'}
				},
				fields : ['PersonID','fullname']
			}),
			fieldLabel : "منبع ",
			pageSize : 25,
			width : 370,
			displayField : "fullname",
			valueField : "PersonID",
			hiddenName : "ReqPersonID",
			listeners :{
				select : function(record){
					el = LoanReport_DebitClassifyObj.formPanel.down("[itemId=cmp_subAgent]");
					el.getStore().proxy.extraParams["PersonID"] = this.getValue();
					el.getStore().load();
				}
			}
		},{
			xtype : "combo",
			store : new Ext.data.SimpleStore({
				proxy: {
					type: 'jsonp',
					url: this.address_prefix + '../../framework/person/persons.data.php?' +
						"task=selectSubAgents",
					reader: {root: 'rows',totalProperty: 'totalCount'}
				},
				fields : ['SubID','SubDesc']
			}),
			fieldLabel : "زیر واحد سرمایه گذار",
			queryMode : "local",
			width : 370,
			displayField : "SubDesc",
			valueField : "SubID",
			hiddenName : "SubAgentID",
			itemId : "cmp_subAgent"
		},{
			xtype : "combo",
			store : new Ext.data.SimpleStore({
				proxy: {
					type: 'jsonp',
					url: this.address_prefix + '../../framework/person/persons.data.php?' +
						"task=selectPersons&UserType=IsCustomer",
					reader: {root: 'rows',totalProperty: 'totalCount'}
				},
				fields : ['PersonID','fullname']
			}),
			fieldLabel : "مشتری",
			displayField : "fullname",
			pageSize : 20,
			width : 370,
			valueField : "PersonID",
			hiddenName : "LoanPersonID"
		},{
			xtype : "combo",
			store : new Ext.data.SimpleStore({
				proxy: {
					type: 'jsonp',
					url: this.address_prefix + '../loan/loan.data.php?task=GetAllLoans',
					reader: {root: 'rows',totalProperty: 'totalCount'}
				},
				fields : ['LoanID','LoanDesc'],
				autoLoad : true					
			}),
			fieldLabel : "نوع وام",
			queryMode : 'local',
			width : 370,
			displayField : "LoanDesc",
			valueField : "LoanID",
			hiddenName : "LoanID"
		},{
			xtype : "combo",
			store : new Ext.data.SimpleStore({
				proxy: {
					type: 'jsonp',
					url: this.address_prefix + '../../framework/baseInfo/baseInfo.data.php?' +
						"task=SelectBranches",
					reader: {root: 'rows',totalProperty: 'totalCount'}
				},
				fields : ['BranchID','BranchName'],
				autoLoad : true					
			}),
			fieldLabel : "شعبه اخذ وام",
			queryMode : 'local',
			width : 370,
			displayField : "BranchName",
			valueField : "BranchID",
			hiddenName : "BranchID"
		},{
			xtype : "combo",
			store : new Ext.data.SimpleStore({
				proxy: {
					type: 'jsonp',
					url: this.address_prefix + '../request/request.data.php?' +
						"task=GetAllStatuses",
					reader: {root: 'rows',totalProperty: 'totalCount'}
				},
				fields : ['InfoID','InfoDesc'],
				autoLoad : true					
			}),
			fieldLabel : "وضعیت وام",
			queryMode : 'local',
			width : 370,
			displayField : "InfoDesc",
			valueField : "InfoID",
			value : "70",
			hiddenName : "StatusID"
		},{
			xtype : "numberfield",
			name : "fromRequestID",
			hideTrigger : true,
			fieldLabel : "شماره وام از"
		},{
			xtype : "numberfield",
			name : "toRequestID",
			hideTrigger : true,
			fieldLabel : "تا شماره"
		},{
			xtype : "shdatefield",
			name : "ComputeDate",
			labelWidth : 120,
			fieldLabel : "محاسبه معوقات تا تاریخ"
		},{
			xtype : "container",
			html : "وضعیت خاتمه&nbsp;:&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;"+
				"<input name=IsEnded type=radio value='YES' > خاتمه یافته &nbsp;&nbsp;" +
				"<input name=IsEnded type=radio value='NO' checked> جاری &nbsp;&nbsp;" +
				"<input name=IsEnded type=radio value=''  > هردو " 
		},{
			xtype : "combo",
			store : new Ext.data.SimpleStore({
				proxy: {
					type: 'jsonp',
					url: this.address_prefix + '../request/request.data.php?' +
						"task=selectFollowLevels",
					reader: {root: 'rows',totalProperty: 'totalCount'}
				},
				fields : ['InfoID','InfoDesc'],
				autoLoad : true					
			}),
			fieldLabel : "سطح پیگیری وام",
			queryMode : 'local',
			width : 370,
			displayField : "InfoDesc",
			valueField : "InfoID",
			hiddenName : "FollowLevelID"
		},{
			xtype : "numberfield",
			maxValue : 100,
			minValue : 0,
			value : 0,
			colspan : 2,
			hideTrigger : true,
			fieldLabel : "درصدی از مبلغ قسط که اگر مانده باشد، وام معوق در نظر گرفته نشود",
			labelWidth : 320,
			width : 360,
			name : "RemainPercent"
		},{
			xtype : "fieldset",
			title : "ستونهای گزارش",
			colspan :2,
			items :[<?= $page_rpg->ReportColumns() ?>]
		},{
			xtype : "fieldset",
			colspan :2,
			title : "رسم نمودار",
			items : [<?= $page_rpg->GetChartItems("LoanReport_DebitClassifyObj","mainForm","installments.php") ?>]
		}],
		buttons : [{
			text : "گزارش ساز",
			iconCls : "db",
			handler : function(){ReportGenerator.ShowReportDB(
						LoanReport_DebitClassifyObj, 
						<?= $_REQUEST["MenuID"] ?>,
						"mainForm",
						"formPanel"
						);}
		},'->',{
			text : "مشاهده گزارش",
			handler : Ext.bind(this.showReport,this),
			iconCls : "report"
		},{
			text : "خروجی excel",
			handler : Ext.bind(this.showReport,this),
			listeners : {
				click : function(){
					LoanReport_DebitClassifyObj.get('excel').value = "true";
				}
			},
			iconCls : "excel"
		},{
			text : "پاک کردن گزارش",
			iconCls : "clear",
			handler : function(){
				LoanReport_DebitClassifyObj.formPanel.getForm().reset();
				LoanReport_DebitClassifyObj.get("mainForm").reset();
			}			
		}]
	});
	
	this.formPanel.getEl().addKeyListener(Ext.EventObject.ENTER, function(keynumber,e){
		
		LoanReport_DebitClassifyObj.showReport();
		e.preventDefault();
		e.stopEvent();
		return false;
	});
}

LoanReport_DebitClassifyObj = new LoanReport_DebitClassify();
</script>
<form id="mainForm">
	<center><br>
		<div id="main" ></div>
	</center>
	<input type="hidden" name="excel" id="excel">
</form>