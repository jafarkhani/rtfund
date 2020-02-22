<?php
//-----------------------------
//	Programmer	: SH.Jafarkhani
//	Date		: 94.06
//-----------------------------

require_once '../../header.inc.php';
require_once "ReportGenerator.class.php";
require_once '../docs/doc.class.php';
require_once inc_CurrencyModule;

if(isset($_REQUEST["show"]))
{
	$query = "select 
			b1.essence,
			b1.BlockCode kol_code,
			b1.BlockDesc kol_desc,
			DocType,
			DocDate,
			substr(g2j(DocDate),6,2) DocMonth,
			di.details,
			di.details,
			sum(DebtorAmount) DSUM, 
			sum(CreditorAmount) CSUM
			
		from ACC_DocItems di
			join ACC_docs d using(docID)
			join ACC_CostCodes cc using(CostID)
			left join ACC_blocks b1 on(cc.level1=b1.BlockID)
			left join BaseInfo b on(di.TafsiliType=InfoID AND TypeID=2)
			
		where d.CycleID=" . $_SESSION["accounting"]["CycleID"];
	
	$whereParam = array();
	
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
	if(!empty($_POST["BranchID"]))
	{
		$query .= " AND d.BranchID = :b ";
		$whereParam[":b"] = $_POST["BranchID"];
	} 
	if(!empty($_POST["level1"]))
	{
		$query .= " AND cc.level1 = :l1 ";
		$whereParam[":l1"] = $_POST["level1"];
	} 
	 
	if(!isset($_REQUEST["IncludeRaw"]))
		$query .= " AND d.StatusID != " . ACC_STEPID_RAW;
	
	if($_POST["ReportDate"] == "month")
		$groupDate = "if(DocType in(".DOCTYPE_ENDCYCLE.",".DOCTYPE_STARTCYCLE."), DocDate, substr(g2j(DocDate),6,2)  )";
	else
		$groupDate = "DocDate";
	
	$query .= " group by if ( DocType not in(".DOCTYPE_ENDCYCLE.",".DOCTYPE_STARTCYCLE.") , 2, DocType),
						 if(DocType in(".DOCTYPE_ENDCYCLE.",".DOCTYPE_STARTCYCLE."), DocDate, $groupDate ),
						 b1.BlockDesc ";
	$query .= " order by b1.BlockCode, if ( DocType not in(".DOCTYPE_ENDCYCLE.",".DOCTYPE_STARTCYCLE.") , 2, DocType),
						if(DocType in(".DOCTYPE_ENDCYCLE.",".DOCTYPE_STARTCYCLE."), DocDate, $groupDate  ),
						if(DebtorAmount>0,0,1),b1.BlockCode,DSUM,CSUM";
	
	$dataTable = PdoDataAccess::runquery($query, $whereParam);
	
	if($_SESSION["USER"]["UserName"] == "admin")
	{
		//print_r(ExceptionHandler::PopAllExceptions());
		//echo PdoDataAccess::GetLatestQueryString();
	}
		
	$rpg = new ReportGenerator();
	$rpg->rowNumber = true;
	$rpg->page_size = 30;
	$rpg->paging = true;
	$rpg->excel = !empty($_POST["excel"]);
	$rpg->mysql_resource = $dataTable;
	if(!$rpg->excel)
	{
		BeginReport();
		echo 
		"<table style='border:2px groove #9BB1CD;border-collapse:collapse;width:100%'><tr>
				<td width=60px><img src='/framework/icons/logo.jpg' style='width:120px'></td>
				<td align='center' style='height:100px;vertical-align:middle;font-family:titr;font-size:15px'>
					گزارش دفتر کل
					 <br> ".
				 "دوره سال " . $_SESSION["accounting"]["CycleID"] .
				"</td>
				<td width='200px' align='center' style='font-family:nazanin;font-size:13px'>تاریخ تهیه گزارش : " 
			. DateModules::shNow() . "<br>";
		if(!empty($_POST["fromDate"]))
		{
			echo "<br>گزارش از تاریخ : " . $_POST["fromDate"] . ($_POST["toDate"] != "" ? " - " . $_POST["toDate"] : "");
		}
		echo "</td></tr></table>";
	}

	function DocDateRender($row, $value){
		
		if($row["DocType"] == DOCTYPE_STARTCYCLE || $row["DocType"] == DOCTYPE_ENDCYCLE || $_POST["ReportDate"] == "day")
			return DateModules::miladi_to_shamsi ($value);
		
		$DocDate = DateModules::miladi_to_shamsi($value);
		$year = DateModules::GetYear($DocDate);
		$month = DateModules::GetMonth($DocDate);
		return $year . "/" . $month . "/" . DateModules::DaysOfMonth($year,$month*1);
	}
	
	function RemainRender(&$row, $value, $x, $prevRow){
		
		if($prevRow["kol_code"] != $row["kol_code"])
			$prevRow["Sum"] = 0;
		
		$row["Sum"] = $prevRow["Sum"] + $row["DSUM"] - $row["CSUM"] ;
			//($row["essence"] == "DEBTOR" ? $row["DSUM"] - $row["CSUM"] : $row["CSUM"] - $row["DSUM"] );
		return number_format($row["Sum"]);
	}
	
	function GroupRender($row, $index){
		return "[ " . $row["kol_code"] . " ] " . $row["kol_desc"];
	}
	
	function DocDescRender($row, $value){
		
		if($row["DocType"] == DOCTYPE_STARTCYCLE)
			return "افتتاحیه";
		if($row["DocType"] == DOCTYPE_ENDCYCLE)
			return "اختتامیه";
		
		return DateModules::GetMonthName($row["DocMonth"]*1);
	}
	
	$rpg->addColumn("کل", "kol_code");
	
	$rpg->groupField = "kol_code";
	$rpg->groupLabel = true;
	$rpg->groupLabelRender = "GroupRender";
	$rpg->groupPerPage = true;
	$rpg->GroupSortSource = false;
	
	$rpg->addColumn("تاریخ سند", "DocDate", "DocDateRender");
	$rpg->addColumn("شرح سند", "DocType", "DocDescRender");
	$col = $rpg->addColumn("بدهکار", "DSUM", "ReportMoneyRender");
	$col->EnableSummary();
	$col = $rpg->addColumn("بستانکار", "CSUM", "ReportMoneyRender");
	$col->EnableSummary();
	$col = $rpg->addColumn("مانده", "essence", "RemainRender");

	echo $rpg->generateReport();
	die();
}
?>
<script>
AccReport_kolBook.prototype = {
	TabID : '<?= $_REQUEST["ExtTabID"]?>',
	address_prefix : "<?= $js_prefix_address?>",

	get : function(elementID){
		return findChild(this.TabID, elementID);
	}
}

AccReport_kolBook.prototype.showReport = function(btn, e)
{
	this.form = this.get("mainForm")
	this.form.target = "_blank";
	this.form.method = "POST";
	this.form.action =  this.address_prefix + "kolBook.php?show=true";
	this.form.submit();
	this.get("excel").value = "";
	return;
}

function AccReport_kolBook()
{
	this.formPanel = new Ext.form.Panel({
		renderTo : this.get("main"),
		frame : true,
		layout :{
			type : "table",
			columns :2
		},
		bodyStyle : "text-align:right;padding:5px",
		title : "گزارش  دفتر کل",
		defaults : {
			labelWidth :120
		},
		width : 600,
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
			xtype : "shdatefield",
			name : "fromDate",
			fieldLabel : "تاریخ سند از"
		},{
			xtype : "shdatefield",
			name : "toDate",
			fieldLabel : "تا"
		},{
			xtype : "container",
			colspan : 2,
			html : "<input type=checkbox checked name=IncludeRaw> گزارش شامل اسناد پیش نویس نیز باشد" 
		},{
			xtype : "fieldset",
			title : "گزارش بر اساس",
			colspan : 2,
			html : 	"<input type=radio checked name=ReportDate value='month'>ماهانه" + "&nbsp;&nbsp;&nbsp; " + 
					"<input type=radio name=ReportDate value='day'>روزانه" + "&nbsp;&nbsp;&nbsp; "
		}],
		buttons : [{
			text : "مشاهده گزارش",
			handler : Ext.bind(this.showReport,this),
			iconCls : "report"
		},{
			text : "خروجی excel",
			handler : Ext.bind(this.showReport,this),
			listeners : {
				click : function(){
					AccReport_kolBookObj.get('excel').value = "true";
				}
			},
			iconCls : "excel"
		},{
			text : "پاک کردن گزارش",
			iconCls : "clear",
			handler : function(){
				AccReport_kolBookObj.formPanel.getForm().reset();
				AccReport_kolBookObj.get("mainForm").reset();
			}			
		}]
	});
}

AccReport_kolBookObj = new AccReport_kolBook();
</script>
<form id="mainForm">
	<center><br>
		<div id="main" ></div>
	</center>
	<input type="hidden" name="excel" id="excel">
</form>