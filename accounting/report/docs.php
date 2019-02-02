<?php
//-----------------------------
//	Programmer	: SH.Jafarkhani
//	Date		: 94.06
//-----------------------------

require_once '../header.inc.php';
require_once "ReportGenerator.class.php";

$page_rpg = new ReportGenerator("mainForm","AccReport_docsObj");
$page_rpg->addColumn("شماره سند", "LocalNo");
$col = $page_rpg->addColumn("تاریخ سند", "DocDate");
$col->type = "date";
$page_rpg->addColumn("ثبت کننده سند", "regPerson");
$page_rpg->addColumn("شرح سند", "description");
$page_rpg->addColumn("جمع بدهکار", "bdSum");
$page_rpg->addColumn("جمع بسنانکار", "bsSum");

function GetData(){
	
	if(!isset($_SESSION["accounting"]))
		$_SESSION["accounting"]["CycleID"] = DateModules::GetYear(DateModules::shNow());
	
	$userFields = ReportGenerator::UserDefinedFields();
	$query = "select d.*, 
					concat(fname,' ',lname) as regPerson, 
					sum(CreditorAmount) bsSum,
					sum(DebtorAmount) bdSum".
					($userFields != "" ? "," . $userFields : "")."
			from ACC_docs d
			join ACC_DocItems di using(docID)
			left join ACC_tafsilis t on(di.TafsiliID=t.TafsiliID)
			left join ACC_tafsilis t2 on(di.TafsiliID2=t2.TafsiliID)
			join BSC_persons p on(RegPersonID=PersonID)
			where d.CycleID=" . $_SESSION["accounting"]["CycleID"];
	
	$whereParam = array();
	
	if(session::IsPortal() && isset($_REQUEST["dashboard_show"]))
	{
		$query .= " AND (t.TafsiliType=".TAFTYPE_PERSONS." AND t.ObjectID=" . $_SESSION["USER"]["PersonID"] .
			" OR t2.TafsiliType=".TAFTYPE_PERSONS." AND t2.ObjectID=" . $_SESSION["USER"]["PersonID"] . ")";
	}
	if(!empty($_POST["BranchID"]))
	{
		$query .= " AND BranchID=:b";
		$whereParam[":b"] = $_POST["BranchID"];
	}	
	if(!empty($_POST["DocType"]))
	{
		$query .= " AND DocType=:dt";
		$whereParam[":dt"] = $_POST["DocType"];
	}	
	if(!empty($_POST["FromLocalNo"]))
	{
		$query .= " AND d.LocalNo >= :td ";
		$whereParam[":td"] = $_POST["FromLocalNo"];
	}
	if(!empty($_POST["ToLocalNo"]))
	{
		$query .= " AND d.LocalNo <= :fd ";
		$whereParam[":fd"] = $_POST["ToLocalNo"];
	}
	if(!empty($_POST["fromDate"]))
	{
		$query .= " AND d.DocDate >= :q1 ";
		$whereParam[":q1"] = DateModules::shamsi_to_miladi($_POST["fromDate"], "-");
	}
	if(!empty($_POST["toDate"]))
	{
		$query .= " AND d.DocDate <= :q2 ";
		$whereParam[":q2"] = DateModules::shamsi_to_miladi($_POST["toDate"], "-");
	}
	if(!empty($_POST["bdFromAmount"]))
	{
		$query .= " AND di.DebtorAmount <= :q3 ";
		$whereParam[":q3"] = $_POST["bdFromAmount"];
	}
	if(!empty($_POST["bdToAmount"]))
	{
		$query .= " AND di.DebtorAmount >= :q4 ";
		$whereParam[":q4"] = $_POST["bdToAmount"];
	}
	if(!empty($_POST["bsFromAmount"]))
	{
		$query .= " AND di.CreditorAmount <= :q5 ";
		$whereParam[":q5"] = $_POST["bsFromAmount"];
	}
	if(!empty($_POST["bsToAmount"]))
	{
		$query .= " AND di.CreditorAmount >= :q6 ";
		$whereParam[":q6"] = $_POST["bsToAmount"];
	}
	if(!empty($_POST["from_regDate"]))
	{
		$query .= " AND d.RegDate >= :q7 ";
		$whereParam[":q7"] = DateModules::shamsi_to_miladi($_POST["from_regDate"], "-");
	}
	if(!empty($_POST["to_regDate"]))
	{
		$query .= " AND d.RegDate <= :q8 ";
		$whereParam[":q8"] = DateModules::shamsi_to_miladi($_POST["to_regDate"], "-");
	}
	if(!empty($_POST["description"]))
	{
		$query .= " AND d.description like :q9 ";
		$whereParam[":q9"] = '%' . $_POST["description"] . "%";
	}
	if(!empty($_POST["details"]))
	{
		$query .= " AND di.details like :q10 ";
		$whereParam[":q10"] = '%' . $_POST["details"] . "%";
	}
	
	if(!isset($_REQUEST["IncludeRaw"]))
		$query .= " AND d.StatusID != " . ACC_STEPID_RAW;
	
	$group = ReportGenerator::GetSelectedColumnsStr();
	$query .= $group == "" ? " group by DocID" : " group by " . $group;
	if(isset($_POST["NotTaraz"]))
		$query .= " having bsSum<>bdSum ";
	$query .= $group == "" ? " order by DocDate,LocalNo" : " order by " . $group;
	
	$dataTable = PdoDataAccess::runquery($query, $whereParam);
	return $dataTable;
}

function ListData($IsDashboard = false){
	
	$rpg = new ReportGenerator();
	$rpg->excel = !empty($_POST["excel"]);
	
	function PrintDocRender($row, $val){
		
		return "<a target=_blank href='../docs/print_doc.php?DocID=" . $row["DocID"] . "'>" . $val . "</a>";
	}
	
	$rpg->addColumn("شماره سند", "LocalNo",$rpg->excel ? "" : "PrintDocRender");
	
	$rpg->addColumn("تاریخ سند", "DocDate","ReportDateRender");
	//$rpg->addColumn("تاریخ ثبت سند", "RegDate","dateRender");
	$rpg->addColumn("ثبت کننده سند", "regPerson");
	$rpg->addColumn("شرح سند", "description");
	$rpg->addColumn("جمع بدهکار", "bdSum","ReportMoneyRender");
	$rpg->addColumn("جمع بسنانکار", "bsSum","ReportMoneyRender");
	
	$rpg->rowColorRender = "RowColorRender";
	function RowColorRender($row){
		if($row["StatusID"] == ACC_STEPID_RAW)
			return "white";
		if($row["StatusID"] == ACC_STEPID_CONFIRM)
			return "#FFFF9E";
		return "#D0F7E2";
	}
	
	$rpg->mysql_resource = GetData();
	$rpg->page_size = 22;
	$rpg->paging = true;
	if(!$rpg->excel && !$IsDashboard)
	{
		BeginReport();
		
		//if($_SESSION["USER"]["UserName"] == "admin")
		//	echo PdoDataAccess::GetLatestQueryString();
		
		$dt = PdoDataAccess::runquery("select * from BSC_branches");
		$branches = array();
		foreach($dt as $row)
			$branches[ $row["BranchID"] ] = $row["BranchName"];
		
		echo "<table style='border:2px groove #9BB1CD;border-collapse:collapse;width:100%'><tr>
				<td width=60px><img src='/framework/icons/logo.jpg' style='width:120px'></td>
				<td align='center' style='height:100px;vertical-align:middle;font-family:titr;font-size:15px'>
					گزارش اسناد حسابداری
					 <br> ".
				 ( empty($_POST["BranchID"]) ? "کلیه شعبه ها" : $branches[$_POST["BranchID"]]) .
				"<br>" . "دوره سال " .
				$_SESSION["accounting"]["CycleID"] .
				"</td>
				<td width='200px' align='center' style='font-family:tahoma;font-size:11px'>تاریخ تهیه گزارش : " 
			. DateModules::shNow() . "<br>";
		if(!empty($_POST["fromDate"]))
		{
			echo "<br>گزارش از تاریخ : " . $_POST["fromDate"] . ($_POST["toDate"] != "" ? " - " . $_POST["toDate"] : "");
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
		ListData(true);	
	
	$page_rpg->mysql_resource = GetData();
	$page_rpg->GenerateChart(false, $_REQUEST["rpcmp_ReportID"]);
	die();	
}
?>
<script>
AccReport_docs.prototype = {
	TabID : '<?= $_REQUEST["ExtTabID"]?>',
	address_prefix : "<?= $js_prefix_address?>",

	get : function(elementID){
		return findChild(this.TabID, elementID);
	}
}

AccReport_docs.prototype.showReport = function(btn, e)
{
	this.form = this.get("mainForm")
	this.form.target = "_blank";
	this.form.method = "POST";
	this.form.action =  this.address_prefix + "docs.php?show=true";
	this.form.submit();
	this.get("excel").value = "";
	return;
}

function AccReport_docs()
{
	this.formPanel = new Ext.form.Panel({
		renderTo : this.get("main"),
		frame : true,
		layout :{
			type : "table",
			columns :2
		},
		bodyStyle : "text-align:right;padding:5px",
		title : "گزارش اسناد",
		defaults : {
			labelWidth :120
		},
		width : 750,
		items :[{
			xtype : "combo",
			colspan : 2,
			width : 400,
			store : new Ext.data.SimpleStore({
				proxy: {
					type: 'jsonp',
					url: "/accounting/global/domain.data.php?task=GetAccessBranches",
					reader: {root: 'rows',totalProperty: 'totalCount'}
				},
				fields : ['BranchID','BranchName'],
				autoLoad : true					
			}),
			fieldLabel : "شعبه",
			queryMode : 'local',
			value : "<?= !isset($_SESSION["accounting"]["BranchID"]) ? "" : $_SESSION["accounting"]["BranchID"] ?>",
			displayField : "BranchName",
			valueField : "BranchID",
			hiddenName : "BranchID"
		},{
			xtype : "combo",
			colspan : 2,
			width : 400,
			store : new Ext.data.SimpleStore({
				proxy: {
					type: 'jsonp',
					url: "/accounting/global/domain.data.php?task=SelectDocTypes",
					reader: {root: 'rows',totalProperty: 'totalCount'}
				},
				fields : ['InfoID','InfoDesc'],
				autoLoad : true					
			}),
			fieldLabel : "نوع سند",
			queryMode : 'local',
			displayField : "InfoDesc",
			valueField : "InfoID",
			hiddenName : "DocType"
		},{
			xtype : "numberfield",
			name : "FromLocalNo",
			hideTrigger : true,
			fieldLabel : "از شماره سند"
		},{
			xtype : "numberfield",
			name : "ToLocalNo",
			hideTrigger : true,
			fieldLabel : "تا شماره سند"
		},{
			xtype : "shdatefield",
			name : "fromDate",
			fieldLabel : "تاریخ سند از"
		},{
			xtype : "shdatefield",
			name : "toDate",
			fieldLabel : "تا"
		},{
			xtype : "numberfield",
			name : "bdFromAmount",
			fieldLabel : "مبلغ ردیف بدهکار از",
			hideTrigger : true
		},{
			xtype : "numberfield",
			name : "bdToAmount",
			fieldLabel : "تا",
			hideTrigger : true
		},{
			xtype : "numberfield",
			name : "bsFromAmount",
			fieldLabel : "مبلغ ردیف بستانکار از",
			hideTrigger : true
		},{
			xtype : "numberfield",
			name : "bsToAmount",
			fieldLabel : "تا",
			hideTrigger : true
		},{
			xtype : "shdatefield",
			name : "from_regDate",
			fieldLabel : "تاریخ ثبت از"
		},{
			xtype : "shdatefield",
			name : "to_regDate",
			fieldLabel : "تا"
		},{
			xtype : "textfield",
			name : "description",
			fieldLabel : "شرح سند"
		},{
			xtype : "textfield",
			name : "details",
			fieldLabel : "جزئیات ردیف"
		},{
			xtype : "container",
			colspan : 2,
			html : "<input type=checkbox name=NotTaraz> اسنادی که تراز نمی باشند"
		},{
			xtype : "container",
			colspan : 2,
			html : "<input type=checkbox checked name=IncludeRaw> گزارش شامل اسناد پیش نویس نیز باشد"
		},{
			xtype : "fieldset",
			title : "ستونهای گزارش",
			colspan :2,
			items :[<?= $page_rpg->ReportColumns() ?>]
		},{
			xtype : "fieldset",
			colspan :2,
			title : "رسم نمودار",
			items : [<?= $page_rpg->GetChartItems("AccReport_docsObj","mainForm","docs.php") ?>]
		}],
		buttons : [{
			text : "گزارش ساز",
			iconCls : "db",
			handler : function(){ReportGenerator.ShowReportDB(
						AccReport_docsObj, 
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
					AccReport_docsObj.get('excel').value = "true";
				}
			},
			iconCls : "excel"
		},{
			text : "پاک کردن گزارش",
			iconCls : "clear",
			handler : function(){
				AccReport_docsObj.formPanel.getForm().reset();
				AccReport_docsObj.get("mainForm").reset();
			}			
		}]
	});
}

AccReport_docsObj = new AccReport_docs();
</script>
<form id="mainForm">
	<center><br>
		<div id="main" ></div>
	</center>
	<input type="hidden" name="excel" id="excel">
</form>