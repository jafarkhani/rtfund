<?php

require_once '../../header.inc.php';
require_once "../request/request.class.php";
require_once "../request/request.data.php";
require_once "ReportGenerator.class.php";

function ReqPersonRender($row,$value){
	return $value == "" ? "منابع داخلی" : $value;
}
	
$page_rpg = new ReportGenerator("mainForm","LoanReport_installmentsObj");
$page_rpg->addColumn("شماره وام", "RRequestID");
$page_rpg->addColumn("نوع وام", "LoanDesc");
$page_rpg->addColumn("منبع", "ReqFullname", "ReqPersonRender");
$page_rpg->addColumn("زیرواحد سرمایه گذار", "SubDesc");
$page_rpg->addColumn("وضعیت", "StatusDesc");
$col = $page_rpg->addColumn("تاریخ درخواست", "ReqDate");
$col->type = "date";
$page_rpg->addColumn("مبلغ درخواست", "ReqAmount");
$page_rpg->addColumn("مشتری", "LoanFullname");
$page_rpg->addColumn("شعبه", "BranchName");
$page_rpg->addColumn("مبلغ تایید شده", "PartAmount");
$page_rpg->addColumn("مبلغ پرداخت شده", "SumPayments");
$page_rpg->addColumn("تعداد اقساط", "InstallmentCount");
$page_rpg->addColumn("تنفس(ماه)", "DelayMonths");
$page_rpg->addColumn("کارمزد مشتری", "CustomerWage");
$page_rpg->addColumn("کارمزد صندوق", "FundWage");
$page_rpg->addColumn("درصد دیرکرد", "ForfeitPercent");
$page_rpg->addColumn("تضامین", "tazamin");
$col = $page_rpg->addColumn("تاریخ قسط", "InstallmentDate");
$col->type = "date";
$page_rpg->addColumn("مبلغ قسط", "InstallmentAmount");

//...........................
$col = $page_rpg->addColumn("کارمزد تاخیر", "SumLate");									$col->IsQueryField = false;
$col = $page_rpg->addColumn("جریمه", "SumPnlt", "ReportMoneyRender");					$col->IsQueryField = false;
$col = $page_rpg->addColumn("پرداخت مشتری", "SumPayedAmount", "ReportMoneyRender");		$col->IsQueryField = false;
//...........................
$col = $page_rpg->addColumn("اصل", "pure", "ReportMoneyRender");						$col->IsQueryField = false;
$col = $page_rpg->addColumn("کارمزد", "wage", "ReportMoneyRender");						$col->IsQueryField = false;
$col = $page_rpg->addColumn("تاریخ پرداخت", "PayedDate", "ReportDateRender");			$col->IsQueryField = false;
$col = $page_rpg->addColumn("مبلغ پرداخت", "PayedAmount", "ReportMoneyRender");			$col->IsQueryField = false;
$col = $page_rpg->addColumn("روز تعجیل", "EarlyDays");									$col->IsQueryField = false;
$col = $page_rpg->addColumn("مبلغ تعجیل", "EarlyAmount", "ReportMoneyRender");			$col->IsQueryField = false;
$col = $page_rpg->addColumn("روز تاخیر", "PnltDays");									$col->IsQueryField = false;
$col = $page_rpg->addColumn("کارمزد تاخیر", "cur_late", "ReportMoneyRender");			$col->IsQueryField = false;
$col = $page_rpg->addColumn("جریمه", "cur_pnlt", "ReportMoneyRender");					$col->IsQueryField = false;
$col = $page_rpg->addColumn("پرداخت از اصل", "pay_pure", "ReportMoneyRender");			$col->IsQueryField = false;
$col = $page_rpg->addColumn("پرداخت از کارمزد", "pay_wage", "ReportMoneyRender");		$col->IsQueryField = false;
$col = $page_rpg->addColumn("پرداخت از کارمزد تاخیر", "pay_late", "ReportMoneyRender");	$col->IsQueryField = false;
$col = $page_rpg->addColumn("پرداخت از جریمه", "pay_pnlt", "ReportMoneyRender");		$col->IsQueryField = false;
$col = $page_rpg->addColumn("مانده اصل", "remain_pure", "ReportMoneyRender");			$col->IsQueryField = false;
$col = $page_rpg->addColumn("مانده کارمزد", "remain_wage", "ReportMoneyRender");		$col->IsQueryField = false;
$col = $page_rpg->addColumn("مانده کارمزد تاخیر", "remain_late", "ReportMoneyRender");	$col->IsQueryField = false;
$col = $page_rpg->addColumn("مانده جریمه", "remain_pnlt", "ReportMoneyRender");			$col->IsQueryField = false;
$col = $page_rpg->addColumn("مانده قسط", "remain", "ReportMoneyRender");				$col->IsQueryField = false;


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

		if($key == "IsEndedInclude" || $key == "IsPayRowsInclude" || $key == "RemainStatus")
			continue;

		$prefix = "";
		switch($key)
		{
			case "fromRequestID":
			case "toRequestID":
				$prefix = "i.";
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

	$where .= isset($_POST["IsEndedInclude"]) ? " AND IsEnded='YES'" : "";
			
}	

function GetData(){
	
	ini_set("memory_limit", "1000M");
	ini_set("max_execution_time", "600");
	
	$where = "";
	$whereParam = array();
	$userFields = ReportGenerator::UserDefinedFields();
	MakeWhere($where, $whereParam);
	
	$query = "select i.*,r.*,l.*,p.*,
				i.InstallmentAmount - i.wage as pure,
				sb.SubDesc,
				tazamin,
				SumPayments,
				r.RequestID as RRequestID,
				concat_ws(' ',p1.fname,p1.lname,p1.CompanyName) ReqFullname,
				concat_ws(' ',p2.fname,p2.lname,p2.CompanyName) LoanFullname,
				BranchName".
				($userFields != "" ? "," . $userFields : "")."
				
			from LON_installments i
			join LON_requests r using(RequestID)
			left join BaseInfo bi on(bi.TypeID=5 AND bi.InfoID=r.StatusID)
			left join BSC_SubAgents sb on(sb.SubID=SubAgentID)
			join LON_ReqParts p on(r.RequestID=p.RequestID AND p.IsHistory='NO')
			left join LON_loans l using(LoanID)
			join BSC_branches using(BranchID)
			left join BSC_persons p1 on(p1.PersonID=r.ReqPersonID)
			left join BSC_persons p2 on(p2.PersonID=r.LoanPersonID)
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
				select RequestID,sum(PayAmount) SumPayments 
				from LON_payments 
				join (select SourceID1,SourceID3 from ACC_DocItems where SourceType=".DOCTYPE_LOAN_PAYMENT." group by SourceID1,SourceID3)t 
					on(t.SourceID1=RequestID AND t.SourceID3=PayID)
				where 1=1 
				group by RequestID			
			)t_pay on(r.RequestID=t_pay.RequestID)
			where i.history='NO' AND i.IsDelayed='NO' " . $where;
	
	$group = ReportGenerator::GetSelectedColumnsStr();
	$query .= $group == "" ? " group by i.InstallmentID" : " group by " . $group;
	$query .= $group == "" ? " order by i.InstallmentID" : " order by " . $group;		
	
	$dataTable = PdoDataAccess::runquery($query, $whereParam);
	
	if($_SESSION["USER"]["UserName"] == "admin")
	{
		ini_set("display_errors", "On");
		//print_r(ExceptionHandler::PopAllExceptions());
		//echo PdoDataAccess::GetLatestQueryString();
	}
	//.....................................
	$payColumns = array("pure","wage","PayedDate","PayedAmount","EarlyDays","EarlyAmount","PnltDays",
			"cur_late","cur_pnlt","pay_pure", "pay_wage", "pay_late", "pay_pnlt", "remain_pure", "remain_wage",
			"remain_late","remain_pnlt");
	foreach($payColumns as $key)
	{
		if(!empty($_POST["reportcolumn_ord_" . $key]))
		{
			$_POST["IsPayRowsInclude"] = true;
			break;
		}
	}
		
	//.....................................
	$computeArr = array();
	$returnArr = array();
	for($index=0; $index<count($dataTable); $index++)
	{
		$MainRow = &$dataTable[$index];

		if(!isset($computeArr[ $MainRow["RequestID"] ]))
		{
			$computeArr[ $MainRow["RequestID"] ] = array(
				"compute" => LON_Computes::ComputePayments($MainRow["RequestID"]),
				"computIndex" => 0,
				"partObj" => LON_ReqParts::GetValidPartObj($MainRow["RequestID"])
			);
		}
		$ref = & $computeArr[ $MainRow["RequestID"] ];
		for(; $ref["computIndex"] < count($ref["compute"]); $ref["computIndex"]++)
		{
			$row = $ref["compute"][$ref["computIndex"]];
			if($row["type"] != "installment")
				continue;
			
			//........................................................
			if($row["InstallmentID"] == $MainRow["InstallmentID"])
			{
				$MainRow["remain"] =  count($row["pays"])>0 ? $row["pays"][ count($row["pays"])-1 ]["remain"]*1 : 
										$MainRow["InstallmentAmount"];
				
				switch($_POST["RemainStatus"])
				{
					case "paid":
						if(count($row["pays"]) == 0)
							continue;
						break;
					case "notPaid":
						if(count($row["pays"]) > 0)
							continue;
						break;
					case "fullPaid":
						if( $row["pays"][ count($row["pays"])-1 ]["remain"]*1 > 0)
							continue;
						break;
				}
				
				//........................................................
				$MainRow["SumPayedAmount"] = 0;
				$MainRow["SumLate"] = 0;
				$MainRow["SumPnlt"] = 0;
				foreach($row["pays"] as $prow)
				{
					$MainRow["SumPayedAmount"] += $prow["PayedAmount"]*1;
					$MainRow["SumLate"] += $prow["cur_late"]*1;
					$MainRow["SumPnlt"] += $prow["cur_pnlt"]*1;
				}
				//........................................................
				
				if(isset($_POST["IsPayRowsInclude"]))
				{
					for($k=0; $k < count($row["pays"]); $k++)
					{  
						$payRow = $row["pays"][$k];
						$MainRow = array_merge($MainRow, $payRow);
						$returnArr[] = $MainRow;
					}
					if(count($row["pays"]) == 0)
					{
						$MainRow["pay_pure"] = 0;
						$MainRow["pay_wage"] = 0;
						$MainRow["pay_late"] = 0;
						$MainRow["pay_pnlt"] = 0;
						$MainRow["EarlyDays"] = 0;
						$MainRow["EarlyAmount"] = 0;
						$MainRow["PnltDays"] = 0;
						$MainRow["cur_late"] = 0;
						$MainRow["cur_pnlt"] = 0;
						$MainRow["remain_pure"] = 0;
						$MainRow["remain_wage"] = 0;
						$MainRow["remain_late"] = 0;
						$MainRow["remain_pnlt"] = 0;
						$MainRow["PayedDate"] = "";
						$MainRow["PayedAmount"] = 0;
						$returnArr[] = $MainRow;
					}
				}
				else
				{
					$returnArr[] = $MainRow;
				}

				$ref["computIndex"]++;
				break;
			}
		}
	}
	
	return $returnArr;
}	
	
function ListData($IsDashboard = false){
	
	$rpg = new ReportGenerator();
	$rpg->excel = !empty($_POST["excel"]);
	$rpg->mysql_resource = GetData();
	
	function endedRender($row,$value){
		return ($value == "YES") ? "خاتمه" : "جاری";
	}
	
	function LoanReportRender($row,$value){
		return "<a href=LoanPayment.php?show=tru&RequestID=" . $value . " target=blank >" . $value . "</a>";
	}
	
	$col = $rpg->addColumn("شماره وام", "RRequestID", "LoanReportRender");
	$col->ExcelRender = false;
	$col->rowspaning = true;
	$col->rowspanByFields = array("RRequestID");
	
	$col = $rpg->addColumn("نوع وام", "LoanDesc");
	$col->rowspaning = true;
	$col->rowspanByFields = array("RRequestID");
	
	$col = $rpg->addColumn("منبع", "ReqFullname");
	$col->rowspaning = true;
	$col->rowspanByFields = array("RRequestID");
	
	$col = $rpg->addColumn("زیرواحد سرمایه گذار", "SubDesc");
	$col->rowspaning = true;
	$col->rowspanByFields = array("RRequestID");
	

	$col = $rpg->addColumn("تاریخ درخواست", "ReqDate", "ReportDateRender");
	$col->rowspaning = true;
	$col->rowspanByFields = array("RRequestID");
	
	$col = $rpg->addColumn("مبلغ درخواست", "ReqAmount", "ReportMoneyRender");
	$col->EnableSummary();
	$col->rowspaning = true;
	$col->rowspanByFields = array("RRequestID");
	
	$col = $rpg->addColumn("مشتری", "LoanFullname");
	$col->rowspaning = true;
	$col->rowspanByFields = array("RRequestID");
	
	$col = $rpg->addColumn("شعبه", "BranchName");
	$col->rowspaning = true;
	$col->rowspanByFields = array("RRequestID");
	
	$col = $rpg->addColumn("مبلغ تایید شده", "PartAmount", "ReportMoneyRender");
	$col->rowspaning = true;
	$col->rowspanByFields = array("RRequestID");
	
	$col = $rpg->addColumn("مبلغ پرداخت شده", "SumPayments", "ReportMoneyRender");
	$col->rowspaning = true;
	$col->rowspanByFields = array("RRequestID");
	
	$col = $rpg->addColumn("تعداد اقساط", "InstallmentCount");
	$col->rowspaning = true;
	$col->rowspanByFields = array("RRequestID");
	
	$col = $rpg->addColumn("تنفس(ماه)", "DelayMonths");
	$col->rowspaning = true;
	$col->rowspanByFields = array("RRequestID");
	
	$col = $rpg->addColumn("کارمزد مشتری", "CustomerWage");
	$col->rowspaning = true;
	$col->rowspanByFields = array("RRequestID");
	
	$col = $rpg->addColumn("کارمزد صندوق", "FundWage");
	$col->rowspaning = true;
	$col->rowspanByFields = array("RRequestID");
	
	$col = $rpg->addColumn("درصد دیرکرد", "ForfeitPercent");
	$col->rowspaning = true;
	$col->rowspanByFields = array("RRequestID");
	
	$col = $rpg->addColumn("تضامین", "tazamin");
	$col->rowspaning = true;
	$col->rowspanByFields = array("RRequestID");
	
	
	$col = $rpg->addColumn("تاریخ قسط", "InstallmentDate", "ReportDateRender");
	$col->rowspaning = true;
	$col->rowspanByFields = array("RRequestID","InstallmentDate");
	
	$col = $rpg->addColumn("مبلغ قسط", "InstallmentAmount", "ReportMoneyRender");
	$col->rowspaning = true;
	$col->rowspanByFields = array("RRequestID","InstallmentDate");
	$col->EnableSummary();
	
	if(empty($_POST["IsPayRowsInclude"]))
	{
		$col = $rpg->addColumn("کارمزد تاخیر", "SumLate", "ReportMoneyRender");
		$col->rowspaning = true;
		$col->rowspanByFields = array("RRequestID","InstallmentDate");
		$col->EnableSummary();
		
		$col = $rpg->addColumn("جریمه", "SumPnlt", "ReportMoneyRender");
		$col->rowspaning = true;
		$col->rowspanByFields = array("RRequestID","InstallmentDate");
		$col->EnableSummary();
		
		$col = $rpg->addColumn("پرداخت مشتری", "SumPayedAmount", "ReportMoneyRender");
		$col->rowspaning = true;
		$col->rowspanByFields = array("RRequestID","InstallmentDate");
		$col->EnableSummary();

		$col = $rpg->addColumn("مانده قسط", "remain", "ReportMoneyRender");
		$col->rowspaning = true;
		$col->rowspanByFields = array("RRequestID","InstallmentDate");
		$col->EnableSummary();
	
	}
	else
	{
		$col = $rpg->addColumn("اصل", "pure", "ReportMoneyRender");
		$col->rowspaning = true;
		$col->rowspanByFields = array("RRequestID","InstallmentDate");
		$col->EnableSummary();

		$col = $rpg->addColumn("کارمزد", "wage", "ReportMoneyRender");
		$col->rowspaning = true;
		$col->rowspanByFields = array("RRequestID","InstallmentDate");
		$col->EnableSummary();
		
		$rpg->addColumn("تاریخ پرداخت", "PayedDate", "ReportDateRender");
		$col =$rpg->addColumn("مبلغ پرداخت", "PayedAmount", "ReportMoneyRender");
		$col->EnableSummary();
		$rpg->addColumn("روز تعجیل", "EarlyDays");	
		$rpg->addColumn("مبلغ تعجیل", "EarlyAmount", "ReportMoneyRender");	
		$rpg->addColumn("روز تاخیر", "PnltDays");
		$rpg->addColumn("کارمزد تاخیر", "cur_late", "ReportMoneyRender");	
		$rpg->addColumn("جریمه", "cur_pnlt", "ReportMoneyRender");	
		
		$rpg->addColumn("پرداخت از اصل", "pay_pure", "ReportMoneyRender");	
		$rpg->addColumn("پرداخت از کارمزد", "pay_wage", "ReportMoneyRender");	
		$rpg->addColumn("پرداخت از کارمزد تاخیر", "pay_late", "ReportMoneyRender");	
		$rpg->addColumn("پرداخت از جریمه", "pay_pnlt", "ReportMoneyRender");	
		
		$rpg->addColumn("مانده اصل", "remain_pure", "ReportMoneyRender");	
		$rpg->addColumn("مانده کارمزد", "remain_wage", "ReportMoneyRender");	
		$rpg->addColumn("مانده کارمزد تاخیر", "remain_late", "ReportMoneyRender");	
		$rpg->addColumn("مانده جریمه", "remain_pnlt", "ReportMoneyRender");	
		
		$rpg->addColumn("مانده قسط", "remain", "ReportMoneyRender");
	}
	
	if(!$rpg->excel && !$IsDashboard)
	{
		BeginReport();
		echo "<table style='border:2px groove #9BB1CD;border-collapse:collapse;width:100%'><tr>
				<td width=60px><img src='/framework/icons/logo.jpg' style='width:120px'></td>
				<td align='center' style='height:100px;vertical-align:middle;font-family: titr;font-size:15px'>
					گزارش اقساط وام ها 
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
LoanReport_installments.prototype = {
	TabID : '<?= $_REQUEST["ExtTabID"]?>',
	address_prefix : "<?= $js_prefix_address?>",

	get : function(elementID){
		return findChild(this.TabID, elementID);
	}
}

LoanReport_installments.prototype.showReport = function(btn, e)
{
	this.form = this.get("mainForm")
	this.form.target = "_blank";
	this.form.method = "POST";
	this.form.action =  this.address_prefix + "installments.php?show=true";
	this.form.submit();
	this.get("excel").value = "";
	return;
}

function LoanReport_installments()
{		
	this.formPanel = new Ext.form.Panel({
		renderTo : this.get("main"),
		frame : true,
		layout :{
			type : "table",
			columns :2
		},
		bodyStyle : "text-align:right;padding:5px",
		title : "گزارش اقساط وام ها",
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
			fieldLabel : "منبع",
			pageSize : 25,
			width : 370,
			displayField : "fullname",
			valueField : "PersonID",
			hiddenName : "ReqPersonID",
			listeners :{
				select : function(record){
					el = LoanReport_installmentsObj.formPanel.down("[itemId=cmp_subAgent]");
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
			value : "70",
			displayField : "InfoDesc",
			valueField : "InfoID",
			hiddenName : "StatusID"
		},{
			xtype : "combo",
			width : 370,
			colspan : 2, 
			store : new Ext.data.SimpleStore({
				data : [
					["all" , "همه موارد" ],
					["notPaid" , "هیچ مقداری از قسط پرداخت نشده است" ],
					["paid" , "مقداری از قسط یا کل آن پرداخت شده است" ],
					["fullPaid" , "قسط کامل پرداخت شده است" ]
				],
				fields : ['id','value']
			}),
			displayField : "value",
			valueField : "id",
			hiddenName : "RemainStatus",
			fieldLabel : "وضعیت قسط",
			value : "all"
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
			name : "fromInstallmentDate",
			fieldLabel : "تاریخ قسط از"
		},{
			xtype : "shdatefield",
			name : "toInstallmentDate",
			fieldLabel : "تا تاریخ"
		},{
			xtype : "currencyfield",
			name : "fromInstallmentAmount",
			hideTrigger : true,
			fieldLabel : "از مبلغ قسط"
		},{
			xtype : "currencyfield",
			name : "toInstallmentAmount",
			hideTrigger : true,
			fieldLabel : "تا مبلغ قسط"
		},{
			xtype : "container",
			colspan : 2,
			html : "<input type=checkbox name=IsEndedInclude >  گزارش شامل وام های خاتمه یافته نیز باشد"
		},{
			xtype : "container",
			colspan : 2,
			html : "<input type=checkbox name=IsPayRowsInclude >  گزارش شامل ردیف های پرداخت هر قسط نیز باشد"
		},{
			xtype : "fieldset",
			title : "ستونهای گزارش",
			colspan :2,
			items :[<?= $page_rpg->ReportColumns() ?>]
		},{
			xtype : "fieldset",
			colspan :2,
			title : "رسم نمودار",
			items : [<?= $page_rpg->GetChartItems("LoanReport_installmentsObj","mainForm","installments.php") ?>]
		}],
		buttons : [{
			text : "گزارش ساز",
			iconCls : "db",
			handler : function(){ReportGenerator.ShowReportDB(
						LoanReport_installmentsObj, 
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
					LoanReport_installmentsObj.get('excel').value = "true";
				}
			},
			iconCls : "excel"
		},{
			text : "پاک کردن گزارش",
			iconCls : "clear",
			handler : function(){
				LoanReport_installmentsObj.formPanel.getForm().reset();
				LoanReport_installmentsObj.get("mainForm").reset();
			}			
		}]
	});
	
	this.formPanel.getEl().addKeyListener(Ext.EventObject.ENTER, function(keynumber,e){
		
		LoanReport_installmentsObj.showReport();
		e.preventDefault();
		e.stopEvent();
		return false;
	});
}

LoanReport_installmentsObj = new LoanReport_installments();
</script>
<form id="mainForm">
	<center><br>
		<div id="main" ></div>
	</center>
	<input type="hidden" name="excel" id="excel">
</form>
