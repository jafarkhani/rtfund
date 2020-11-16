<?php

require_once '../../header.inc.php';
require_once "../request/request.class.php";
require_once "../request/request.data.php";
require_once "ReportGenerator.class.php";

function ReqPersonRender($row,$value){
	return $value == "" ? "منابع داخلی" : $value;
}
function IsDocRegisteredRender($row,$value){
	return $value == "YES" ? "*" : "";
}
		
$page_rpg = new ReportGenerator("mainForm","LoanReport_controlObj");
$page_rpg->addColumn("شماره وام", "ReqID");
$page_rpg->addColumn("نوع وام", "LoanDesc");
$page_rpg->addColumn("منبع ", "ReqFullname", "ReqPersonRender");
$page_rpg->addColumn("مبلغ درخواست", "ReqAmount");
$page_rpg->addColumn("مشتری", "LoanFullname");
$page_rpg->addColumn("شعبه", "BranchName");
$page_rpg->addColumn("وضعیت", "StatusDesc");
$page_rpg->addColumn("کل مانده وام", "LoanRemain");
$page_rpg->addColumn("مانده اصل", "remain_pure","ReportMoneyRender");
$page_rpg->addColumn("مانده کارمزد", "remain_wage","ReportMoneyRender");
$page_rpg->addColumn("مانده کارمزد تاخیر", "remain_late","ReportMoneyRender");
$page_rpg->addColumn("مانده جریمه", "remain_pnlt","ReportMoneyRender");


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
		if($key == "excel" || $key == "OrderBy" || 
				$key == "OrderByDirection" || 
				$value === "" || 
				strpos($key, "combobox") !== false || 
				strpos($key, "rpcmp") !== false ||
				strpos($key, "reportcolumn_fld") !== false || 
				strpos($key, "reportcolumn_ord") !== false)
			continue;

		if($key == "IsEndedInclude" || $key == "ZeroRemain")
			continue;
		
		if($key == "fromDoc" || $key == "toDoc" || $key == "ComputeDate")
			continue;

		$prefix = "";
		switch($key)
		{
			case "fromRequestID":
			case "toRequestID":
				$prefix = "r.";
				break;
			case "fromReqDate":
			case "toReqDate":
				$value = DateModules::shamsi_to_miladi($value, "-");
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

	$where .= isset($_POST["IsEndedInclude"]) ? 
			" AND r.StatusID in('".LON_REQ_STATUS_CONFIRM."','".LON_REQ_STATUS_ENDED."')" : 
			" AND r.StatusID in('".LON_REQ_STATUS_CONFIRM."')";
}	

global $c_dt;

function GetData(){
	
	ini_set("memory_limit", "1000M");
	ini_set("max_execution_time", "600");
	
	$where = "";
	$whereParam = array();
	$userFields = ReportGenerator::UserDefinedFields();
	MakeWhere($where, $whereParam);
	
	$query = "select r.RequestID,r.*,l.*,p.*,bi.InfoDesc StatusDesc,
				concat_ws(' ',p1.fname,p1.lname,p1.CompanyName) ReqFullname,
				concat_ws(' ',p2.fname,p2.lname,p2.CompanyName) LoanFullname,
				BranchName
				
			from LON_requests r 
			left join BaseInfo bi on(bi.TypeID=5 AND bi.InfoID=StatusID)
			join LON_ReqParts p on(r.RequestID=p.RequestID AND p.IsHistory='NO')
			left join LON_loans l using(LoanID)
			join BSC_branches using(BranchID)
			left join BSC_persons p1 on(p1.PersonID=r.ReqPersonID)
			left join BSC_persons p2 on(p2.PersonID=r.LoanPersonID)
						
			where 1=1 " . $where . " order by r.RequestID";
	
	$dt = PdoDataAccess::runquery($query, $whereParam);
	//print_r(ExceptionHandler::PopAllExceptions());
	//echo PdoDataAccess::GetLatestQueryString();die();
	//------------ get all involved costs -------------------
	global $c_dt;
	$c_dt = PdoDataAccess::runquery("
		select CostID,CostCode,
			concat('[',CostCode,'] ',b3.BlockDesc) CostDesc,
			b1.essence
		from ACC_DocItems 
		join ACC_docs using(DocID)
		join ACC_CostCodes cc using(CostID)
		join ACC_blocks b1 on(level1=b1.BlockID)
		left join ACC_blocks b2 on(level2=b2.BlockID)
		left join ACC_blocks b3 on(level3=b3.BlockID)
		where CycleID=" . $_SESSION["accounting"]["CycleID"] . " AND 
		(cc.param1 = ".ACC_COST_PARAM_LOAN_RequestID." or "
			. "cc.param2=".ACC_COST_PARAM_LOAN_RequestID." or "
			. "cc.param3=".ACC_COST_PARAM_LOAN_RequestID.")
		 group by CostID
		 having sum(DebtorAmount-CreditorAmount)<>0");
	//-------------------------------------------------------
	for($i=0; $i<count($dt); $i++){
		
		$row = &$dt[$i];	
		
		$ComputeDate = !empty($_REQUEST["ComputeDate"]) ? $_REQUEST["ComputeDate"] : "";
		$ComputeArr = LON_Computes::ComputePayments($row["RequestID"],$ComputeDate);
		$totalCompute = array(
			"pure" => 0,
			"wage" => 0,
			"late" => 0,
			"pnlt" => 0
		);
		$totalPay = array(
			"pure" => 0,
			"wage" => 0,
			"late" => 0,
			"pnlt" => 0
		);
		for($i=0; $i<count($ComputeArr); $i++)
		{
			if($ComputeArr[$i]["type"] == "pay")
			{
				$totalPay["pure"] += $ComputeArr[$i]["pure"];
				$totalPay["wage"] += $ComputeArr[$i]["wage"];
				$totalPay["late"] += $ComputeArr[$i]["totallate"];
				$totalPay["pnlt"] += $ComputeArr[$i]["totalpnlt"];
				continue;
			}			
			
			$totalCompute["pure"] += $ComputeArr[$i]["pure"];
			$totalCompute["wage"] += $ComputeArr[$i]["wage"];
			$totalCompute["late"] += $ComputeArr[$i]["totallate"];
			$totalCompute["pnlt"] += $ComputeArr[$i]["totalpnlt"];
			
		}
		$row["compute_pure"] = $totalCompute["pure"];
		$row["compute_wage"] = $totalCompute["wage"];
		$row["compute_late"] = $totalCompute["late"];
		$row["compute_pnlt"] = $totalCompute["pnlt"];
		
		$row["pay_pure"] = $totalPay["pure"];
		$row["pay_wage"] = $totalPay["wage"];
		$row["pay_late"] = $totalPay["late"];
		$row["pay_pnlt"] = $totalPay["pnlt"];
		
		$row["remain_pure"] = $totalCompute["pure"] - $totalPay["pure"];
		$row["remain_wage"] = $totalCompute["wage"] - $totalPay["wage"];
		$row["remain_late"] = $totalCompute["late"] - $totalPay["late"];
		$row["remain_pnlt"] = $totalCompute["pnlt"] - $totalPay["pnlt"];
		
		//---------------------------------------------------------
		
		$where = "";
		$params = array(":r"=>$row["RequestID"]);
		if(!empty($_POST["fromDoc"]))
		{
			$where .= " AND LocalNo >= :fdoc";
			$params[":fdoc"] = $_POST["fromDoc"];
		}
		if(!empty($_POST["toDoc"]))
		{
			$where .= " AND LocalNo <= :tdoc";
			$params[":tdoc"] = $_POST["toDoc"];
		}
		$cv_dt = PdoDataAccess::runquery("
			select CostID,CostCode,sum(CreditorAmount-DebtorAmount) remainCost, blockDesc
				from ACC_DocItems di join ACC_docs using(DocID)
				join ACC_CostCodes cc using(CostID)
				join ACC_blocks on(level3=blockID)
				where CycleID=" . $_SESSION["accounting"]["CycleID"] . " AND 
					(
						if(cc.param1=".ACC_COST_PARAM_LOAN_RequestID.",di.param1=:r,1=0) OR
						if(cc.param2=".ACC_COST_PARAM_LOAN_RequestID.",di.param2=:r,1=0) OR
						if(cc.param3=".ACC_COST_PARAM_LOAN_RequestID.",di.param3=:r,1=0)
					)" . $where . "						
				group by CostID", $params);
		$cv_ref = array();
		foreach($cv_dt as $cv_row)
			$cv_ref[ $cv_row["CostID"] ] = $cv_row["remainCost"];
		
		
		foreach($c_dt as $c_row){
			if($c_row["essence"] == "DEBTOR")
				$row[ $c_row["CostID"] ] = isset($cv_ref[ $c_row["CostID"] ]) ? -1*$cv_ref[ $c_row["CostID"] ] : 0;
			else
				$row[ $c_row["CostID"] ] = isset($cv_ref[ $c_row["CostID"] ]) ? $cv_ref[ $c_row["CostID"] ] : 0;
		}		
	}
	
	return $dt;
}	
	
function ListData($IsDashboard = false){
	
	$rpg = new ReportGenerator();
	$rpg->excel = !empty($_POST["excel"]);
	$rpg->mysql_resource = GetData();
	if($_SESSION["USER"]["UserName"] == "admin")
	{
		//print_r(ExceptionHandler::PopAllExceptions());
		//echo PdoDataAccess::GetLatestQueryString();
	}
	function endedRender($row,$value){
		return ($value == "YES") ? "خاتمه" : "جاری";
	}
	
	function LoanRender($row, $value){
		return "<a href=DebitReport.php?show=tru&RequestID=" . $row["RequestID"] . " target=blank >" . $value . "</a>";
	}
	$col = $rpg->addColumn("شماره وام", "RequestID", "LoanRender");
	$col->ExcelRender = false;
	
	$col = $rpg->addColumn("نوع وام", "LoanDesc");
	$col = $rpg->addColumn("منبع ", "ReqFullname");
	$col = $rpg->addColumn("مشتری", "LoanFullname");
	$rpg->addColumn("وضعیت", "StatusDesc");
	
	//.............................
	
	$col = $rpg->addColumn("اصل", "compute_pure","ReportMoneyRender");
	$col->GroupHeader = "جمع محاسبه شده طبق زیرسیستم تسهیلات";
	$col->headerColor = "#a2ff9c";
	$col->EnableSummary();
	
	$col = $rpg->addColumn("کارمزد", "compute_wage","ReportMoneyRender");
	$col->GroupHeader = "جمع محاسبه شده طبق زیرسیستم تسهیلات";
	$col->headerColor = "#a2ff9c";
	$col->EnableSummary();
	
	$col = $rpg->addColumn("تاخیر", "compute_late","ReportMoneyRender");
	$col->GroupHeader = "جمع محاسبه شده طبق زیرسیستم تسهیلات";
	$col->headerColor = "#a2ff9c";
	$col->EnableSummary();
	
	$col = $rpg->addColumn("جریمه", "compute_pnlt","ReportMoneyRender");
	$col->GroupHeader = "جمع محاسبه شده طبق زیرسیستم تسهیلات";
	$col->headerColor = "#a2ff9c";
	$col->EnableSummary();
	
	//.............................
	
	$col = $rpg->addColumn("اصل", "pay_pure","ReportMoneyRender");
	$col->GroupHeader = "جمع پرداخت شده طبق زیرسیستم تسهیلات";
	$col->headerColor = "#b0edff";
	$col->EnableSummary();
	
	$col = $rpg->addColumn("کارمزد", "pay_wage","ReportMoneyRender");
	$col->GroupHeader = "جمع پرداخت شده طبق زیرسیستم تسهیلات";
	$col->headerColor = "#b0edff";
	$col->EnableSummary();
	
	$col = $rpg->addColumn("تاخیر", "pay_late","ReportMoneyRender");
	$col->GroupHeader = "جمع پرداخت شده طبق زیرسیستم تسهیلات";
	$col->headerColor = "#b0edff";
	$col->EnableSummary();
	
	$col = $rpg->addColumn("جریمه", "pay_pnlt","ReportMoneyRender");
	$col->GroupHeader = "جمع پرداخت شده طبق زیرسیستم تسهیلات";
	$col->headerColor = "#b0edff";
	$col->EnableSummary();
	
	//.............................
	
	$col = $rpg->addColumn("اصل", "remain_pure","ReportMoneyRender");
	$col->GroupHeader = "مانده طبق زیرسیستم تسهیلات";
	$col->headerColor = "#fdff9c";	
	$col->EnableSummary();
	
	$col = $rpg->addColumn("کارمزد", "remain_wage","ReportMoneyRender");
	$col->GroupHeader = "مانده طبق زیرسیستم تسهیلات";
	$col->headerColor = "#fdff9c";	
	$col->EnableSummary();
	
	$col = $rpg->addColumn("تاخیر", "remain_late","ReportMoneyRender");
	$col->GroupHeader = "مانده طبق زیرسیستم تسهیلات";
	$col->headerColor = "#fdff9c";	
	$col->EnableSummary();
	
	$col = $rpg->addColumn("جریمه", "remain_pnlt","ReportMoneyRender");
	$col->GroupHeader = "مانده طبق زیرسیستم تسهیلات";
	$col->headerColor = "#fdff9c";	
	$col->EnableSummary();
	
	global $c_dt;
	foreach($c_dt as $row)
	{
		$col = $rpg->addColumn($row["CostDesc"], $row["CostID"],"ReportMoneyRender");
		$col->GroupHeader = "مانده طبق مالی";
		$col->headerColor = "#ffc4c4";
		$col->EnableSummary();
	}
	
		
	if(!$rpg->excel && !$IsDashboard)
	{
		BeginReport();
		echo "<table style='border:2px groove #9BB1CD;border-collapse:collapse;width:100%'><tr>
				<td width=60px><img src='/framework/icons/logo.jpg' style='width:120px'></td>
				<td align='center' style='height:100px;vertical-align:middle;font-family: titr;font-size:15px'>
					گزارش  کنترل وام با مالی
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
		$rpg->generateReport();
		echo "</div>";
	}
	else
		$rpg->generateReport();
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
LoanReport_control.prototype = {
	TabID : '<?= $_REQUEST["ExtTabID"]?>',
	address_prefix : "<?= $js_prefix_address?>",

	get : function(elementID){
		return findChild(this.TabID, elementID);
	}
}

LoanReport_control.prototype.showReport = function(btn, e)
{
	this.form = this.get("mainForm")
	this.form.target = "_blank";
	this.form.method = "POST";
	this.form.action =  this.address_prefix + "control.php?show=true";
	this.form.submit();
	this.get("excel").value = "";
	return;
}

function LoanReport_control()
{		
	this.formPanel = new Ext.form.Panel({
		renderTo : this.get("main"),
		frame : true,
		layout :{
			type : "table",
			columns :2
		},
		bodyStyle : "text-align:right;padding:5px",
		title : "گزارش کنترل وام با مالی",
		width : 780,
		items :[{
			xtype : "combo",
			store : new Ext.data.SimpleStore({
				proxy: {
					type: 'jsonp',
					url: this.address_prefix + '../../framework/person/persons.data.php?' +
						"task=selectPersons&UserTypes=IsAgent,IsSupporter",
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
					el = LoanReport_controlObj.formPanel.down("[itemId=cmp_subAgent]");
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
			colspan : 2,
			displayField : "BranchName",
			valueField : "BranchID",
			hiddenName : "BranchID"
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
			xtype : "numberfield",
			name : "fromDoc",
			hideTrigger : true,
			fieldLabel : "شماره سند از"
		},{
			xtype : "numberfield",
			name : "toDoc",
			hideTrigger : true,
			fieldLabel : "تا شماره"
		},{
			xtype : "shdatefield",
			name : "fromReqDate",
			fieldLabel : "تاریخ وام از"
		},{
			xtype : "shdatefield",
			name : "toReqDate",
			fieldLabel : "تا تاریخ وام"
		},{
			xtype : "shdatefield",
			name : "ComputeDate",
			fieldLabel : "محاسبه تا تاریخ"
		},{
			xtype : "container",
			colspan : 2,
			html : "<input type=checkbox name=IsEndedInclude >  گزارش شامل وام های خاتمه یافته نیز باشد"
		}],
		buttons : [{
			text : "گزارش ساز",
			iconCls : "db",
			handler : function(){ReportGenerator.ShowReportDB(
						LoanReport_controlObj, 
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
					LoanReport_controlObj.get('excel').value = "true";
				}
			},
			iconCls : "excel"
		},{
			text : "پاک کردن گزارش",
			iconCls : "clear",
			handler : function(){
				LoanReport_controlObj.formPanel.getForm().reset();
				LoanReport_controlObj.get("mainForm").reset();
			}			
		}]
	});
	
	this.formPanel.getEl().addKeyListener(Ext.EventObject.ENTER, function(keynumber,e){
		
		LoanReport_controlObj.showReport();
		e.preventDefault();
		e.stopEvent();
		return false;
	});
}

LoanReport_controlObj = new LoanReport_control();
</script>
<form id="mainForm">
	<center><br>
		<div id="main" ></div>
	</center>
	<input type="hidden" name="excel" id="excel">
</form>