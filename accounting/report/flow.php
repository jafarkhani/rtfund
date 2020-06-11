<?php
//-----------------------------
//	Programmer	: SH.Jafarkhani
//	Date		: 94.06
//-----------------------------

require_once '../../header.inc.php';
require_once "ReportGenerator.class.php";

$x= 0;
function TotalRemainRender(&$row, $value, $x, $prevRow){
	
	$preAmount = !$prevRow ? 0 : $prevRow["Sum"];
	$row["Sum"] = $preAmount + 
			($row["essence"] == "DEBTOR" ? $row["DebtorAmount"] - $row["CreditorAmount"] : $row["CreditorAmount"] - $row["DebtorAmount"] );
	return number_format($row["Sum"]);
}

$page_rpg = new ReportGenerator("mainForm","AccReport_flowObj");
$page_rpg->addColumn("شماره سند", "LocalNo");
$page_rpg->addColumn("کد حساب", "CostCode");
$page_rpg->addColumn("شرح حساب", "CostDesc");
$page_rpg->addColumn("تفصیلی", "TafsiliDesc");
$page_rpg->addColumn("تفصیلی2", "TafsiliDesc2");
$page_rpg->addColumn("تفصیلی3", "TafsiliDesc3");
$page_rpg->addColumn("آیتم1", "ParamValue1");
$page_rpg->addColumn("آیتم2", "ParamValue2");
$page_rpg->addColumn("آیتم3", "ParamValue3");
$col = $page_rpg->addColumn("تاریخ سند", "DocDate");
$col->type = "date";
$page_rpg->addColumn("شرح", "detail");	
$page_rpg->addColumn("مبلغ بدهکار", "DebtorAmount");
$page_rpg->addColumn("مبلغ بستانکار", "CreditorAmount");

$page_rpg->addColumn("مانده", "CreditorAmount", "TotalRemainRender");
	
function MakeWhere(&$where, &$whereParam , $ForRemain = false){
	
	if(session::IsPortal() && isset($_REQUEST["dashboard_show"]))
	{
		$where .= " AND (t.TafsiliType=".TAFSILITYPE_PERSON." AND t.ObjectID=" . $_SESSION["USER"]["PersonID"] .
			" OR t2.TafsiliType=".TAFSILITYPE_PERSON." AND t2.ObjectID=" . $_SESSION["USER"]["PersonID"] . ")";
	}
	
	if(isset($_REQUEST["taraz"])){

		if(!isset($_REQUEST["IncludeStart"]))
			$where .= " AND d.DocType != " . DOCTYPE_STARTCYCLE;
		if(!isset($_REQUEST["IncludeEnd"]))
			$where .= " AND d.DocType not in(" . DOCTYPE_ENDCYCLE . "," . DOCTYPE_CLOSECYCLE . ")";
	}
	else
	{
		if(!isset($_REQUEST["IncludeStart"]))
			$where .= " AND d.DocType != " . DOCTYPE_STARTCYCLE;

		if(!isset($_REQUEST["IncludeEnd"]))
			$where .= " AND d.DocType not in(" . DOCTYPE_ENDCYCLE . "," . DOCTYPE_CLOSECYCLE . ")";
	}
	if(!empty($_REQUEST["CycleID"]))
	{
		$where .= " AND d.CycleID=:c" ;
		$whereParam[":c"] = $_REQUEST["CycleID"];
	}

	if(!isset($_REQUEST["IncludeRaw"]))
		$where .= " AND d.StatusID != " . ACC_STEPID_RAW;

	if(!empty($_REQUEST["EventID"]))
	{
		$where .= " AND d.EventID in (" . $_REQUEST["EventID"] . ")";
	}
		
	if(!empty($_REQUEST["BranchID"]))
	{
		$where .= " AND BranchID=:b";
		$whereParam[":b"] = $_REQUEST["BranchID"];
	}	
	if(!empty($_REQUEST["GroupID"]))
	{
		$where .= " AND b1.GroupID = :gid";
		$whereParam[":gid"] = $_REQUEST["GroupID"];
	}
	if(!empty($_REQUEST["CostID"]))
	{
		$where .= " AND di.CostID = :costid";
		$whereParam[":costid"] = $_REQUEST["CostID"];
	}
	if(!empty($_REQUEST["level1"]))
	{
		$where .= " AND b1.BlockID = :bf1";
		$whereParam[":bf1"] = $_REQUEST["level1"];
	}
	if(!empty($_REQUEST["level2"]))
	{
		$where .= " AND b2.BlockID = :bf2";
		$whereParam[":bf2"] = $_REQUEST["level2"];
	}
	if(!empty($_REQUEST["level3"]))
	{
		$where .= " AND b3.BlockID = :bf3";
		$whereParam[":bf3"] = $_REQUEST["level3"];
	}
	if(!empty($_REQUEST["level4"]))
	{
		$where .= " AND b4.BlockID = :bf4";
		$whereParam[":bf4"] = $_REQUEST["level4"];
	}
	if(isset($_REQUEST["taraz"]) && isset($_REQUEST["TafsiliID"]))
	{
		if($_REQUEST["TafsiliID"] == "")
			$where .= " AND (di.TafsiliID=0 OR di.TafsiliID is null)";
		else
		{
			$where .= " AND (di.TafsiliID = :tid or di.TafsiliID2 = :tid or di.TafsiliID3 = :tid) ";
			$whereParam[":tid"] = $_REQUEST["TafsiliID"];
		}
	}
	if(empty($_REQUEST["taraz"]) && !empty($_REQUEST["TafsiliID"]))
	{
		$where .= " AND (di.TafsiliID = :tid )";
		$whereParam[":tid"] = $_REQUEST["TafsiliID"];
	}
	if(!empty($_REQUEST["TafsiliType"]))
	{
		$where .= " AND (di.TafsiliType = :tt)";
		$whereParam[":tt"] = $_REQUEST["TafsiliType"];
	}
	if(isset($_REQUEST["TafsiliID2"]))
	{
		if($_REQUEST["TafsiliID2"] == "")
		{
			if(isset($_REQUEST["taraz"]))
				$where .= " AND (di.TafsiliID2=0 OR di.TafsiliID2 is null)";
		}
		else
		{
			$where .= " AND di.TafsiliID2 = :tid2 ";
			$whereParam[":tid2"] = $_REQUEST["TafsiliID2"];
		}
	}
	if(isset($_REQUEST["TafsiliID3"]))
	{
		if($_REQUEST["TafsiliID3"] == "")
		{
			if(isset($_REQUEST["taraz"]))
				$where .= " AND (di.TafsiliID3=0 OR di.TafsiliID3 is null)";
		}
		else
		{
			$where .= " AND di.TafsiliID3 = :tid3 ";
			$whereParam[":tid3"] = $_REQUEST["TafsiliID3"];
		}
	}
	if(!empty($_REQUEST["fromLocalNo"]))
	{
		$where .= " AND d.LocalNo >= :lo1 ";
		$whereParam[":lo1"] = $_REQUEST["fromLocalNo"];
	}
	if(!empty($_REQUEST["toLocalNo"]))
	{
		$where .= " AND d.LocalNo <= :lo2 ";
		$whereParam[":lo2"] = $_REQUEST["toLocalNo"];
	}
	if(!$ForRemain && !empty($_REQUEST["fromDate"]))
	{
		$where .= " AND d.docDate >= :q1 ";
		$whereParam[":q1"] = DateModules::shamsi_to_miladi($_REQUEST["fromDate"], "-");
	}
	if(!$ForRemain && !empty($_REQUEST["toDate"]))
	{
		$where .= " AND d.docDate <= :q2 ";
		$whereParam[":q2"] = DateModules::shamsi_to_miladi($_REQUEST["toDate"], "-");
	}
	if(!empty($_REQUEST["description"]))
	{
		$where .= " AND d.description like :des ";
		$whereParam[":des"] = "%" . $_REQUEST["description"] . "%";
	}
	if(!empty($_REQUEST["details"]))
	{
		$where .= " AND di.details like :det ";
		$whereParam[":det"] = "%" . $_REQUEST["details"] . "%";
	}
	
	$index = 1;
	foreach($_POST as $key => $val)
	{
		if(strpos($key, "paramID") === false || empty($val))
			continue;

		$ParamID = preg_replace("/paramID/", "", $key);
		$where .= " AND ( 
				if(cc.param1 = :pid$index, di.param1=:pval$index, 1=0) OR
				if(cc.param2 = :pid$index, di.param2=:pval$index, 1=0) OR
				if(cc.param3 = :pid$index, di.param3=:pval$index, 1=0) 
			)";
		$whereParam[":pid$index"] = $ParamID;
		$whereParam[":pval$index"] = $val;
		$index++;
	}
	
	if(!empty($_REQUEST["PersonID"]))
	{
		$where .= " AND (
			if(t.TafsiliType in(320,200),t.ObjectID=:pid,1=0) OR 
			if(t2.TafsiliType in(320,200),t2.ObjectID=:pid,1=0) OR
			if(t3.TafsiliType in(320,200),t3.ObjectID=:pid,1=0)
		)";
		$whereParam[":pid"] = $_REQUEST["PersonID"];
	}
	
}	
	
function GetData(){
	
	$userFields = ReportGenerator::UserDefinedFields();
	
	$query = "select d.*,di.DebtorAmount,CreditorAmount,
		concat_ws(' - ',di.details,d.description) detail,
		cc.CostCode,
		concat_ws(' - ' , b1.BlockDesc,b2.BlockDesc,b3.BlockDesc,b4.BlockDesc) CostDesc,
		b1.essence,
		t.TafsiliDesc TafsiliDesc,
		t2.TafsiliDesc TafsiliDesc2,
		t3.TafsiliDesc TafsiliDesc3,
		if(p1.ParamType='combo',pi1.ParamValue,di.param1) ParamValue1,
		if(p2.ParamType='combo',pi2.ParamValue,di.param2) ParamValue2,
		if(p3.ParamType='combo',pi3.ParamValue,di.param3) ParamValue3".
		($userFields != "" ? "," . $userFields : "")."
		
		from ACC_DocItems di join ACC_docs d using(DocID)
			join ACC_CostCodes cc using(CostID)
			join ACC_blocks b1 on(level1=b1.BlockID)
			left join ACC_blocks b2 on(level2=b2.BlockID)
			left join ACC_blocks b3 on(level3=b3.BlockID)
			left join ACC_blocks b4 on(level4=b4.BlockID)
			left join ACC_tafsilis t using(TafsiliID)
			left join ACC_tafsilis t2 on(di.TafsiliID2=t2.TafsiliID)
			left join ACC_tafsilis t3 on(di.TafsiliID3=t3.TafsiliID)
			
			left join ACC_CostCodeParams p1 on(p1.ParamID=cc.param1)
			left join ACC_CostCodeParams p2 on(p2.ParamID=cc.param2)
			left join ACC_CostCodeParams p3 on(p3.ParamID=cc.param3)
			left join ParamItems pi1 on(pi1.ParamID=cc.param1 AND p1.ParamType='combo' AND di.param1=pi1.ItemID)
			left join ParamItems pi2 on(pi2.ParamID=cc.param2 AND p2.ParamType='combo' AND di.param2=pi2.ItemID)
			left join ParamItems pi3 on(pi3.ParamID=cc.param3 AND p3.ParamType='combo' AND di.param3=pi3.ItemID)
			
		where 1=1 ";
	
	$where = "";
	$whereParam = array();
		
	MakeWhere($where, $whereParam);
	$query .= $where;
	
	$group = ReportGenerator::GetSelectedColumnsStr();
	$query .= $group == "" ? " " : " group by " . $group;
	$query .= $group == "" ? " order by d.DocDate,DebtorAmount,CreditorAmount" : " order by " . $group;	
	
	$dataTable = PdoDataAccess::runquery($query, $whereParam);
	
	//print_r(ExceptionHandler::PopAllExceptions());
	//if($_SESSION["USER"]["UserName"] == "admin")
	//	echo PdoDataAccess::GetLatestQueryString ();
	//-------------------------- previous remaindar ----------------------------
	if(!empty($_REQUEST["fromDate"]))
	{
		$query = "select b1.essence,sum(if(b1.essence='DEBTOR',DebtorAmount-CreditorAmount,CreditorAmount-DebtorAmount)) amount

			from ACC_DocItems di join ACC_docs d using(DocID)
				join ACC_CostCodes cc using(CostID)
				join ACC_blocks b1 on(level1=b1.BlockID)
				left join ACC_blocks b2 on(level2=b2.BlockID)
				left join ACC_blocks b3 on(level3=b3.BlockID)
				left join ACC_blocks b4 on(level4=b4.BlockID)
				left join BaseInfo b on(TypeID=2 AND di.TafsiliType=InfoID)
				left join ACC_tafsilis t using(TafsiliID)
				left join BaseInfo bi2 on(bi2.TypeID=2 AND di.TafsiliType2=bi2.InfoID)
				left join ACC_tafsilis t2 on(di.TafsiliID2=t2.TafsiliID)
			where d.DocDate < :fd";
		
		$where = "";
		$whereParam = array(":fd" => DateModules::shamsi_to_miladi($_REQUEST["fromDate"], "-"));

		MakeWhere($where, $whereParam, true);
		$query .= $where;

		$DT = PdoDataAccess::runquery($query, $whereParam);
		$BeforeAmount = $DT[0]["amount"];
		$dataTable = array_merge(array( array(
			"DocID" => "",
			"LocalNo" => "",
			"CostDesc" => "مانده از قبل",
			"TafsiliDesc" => "",
			"TafsiliDesc2" => "",
			"DocDate" => "",
			"detail" => "",
			"essence" => $DT[0]["essence"] ,
			"DebtorAmount" => $DT[0]["essence"] == "DEBTOR" ? ($BeforeAmount>0 ? $BeforeAmount : 0) : ($BeforeAmount<0 ? abs($BeforeAmount) : 0),
			"CreditorAmount" => $DT[0]["essence"] == "CREDITOR" ? ($BeforeAmount>0 ? $BeforeAmount : 0) : ($BeforeAmount<0 ? abs($BeforeAmount) : 0)
		)), $dataTable);
	}
	
	return $dataTable;
}

function ListData($IsDashboard = false){
	
	$rpg = new ReportGenerator();
	$rpg->excel = !empty($_POST["excel"]);
	
	function PrintDocRender($row, $val){
		
		return "<a target=_blank href='../docs/print_doc.php?DocID=" . $row["DocID"] . "'>" . $val . "</a>";
	}
	
	$dataTable = GetData();
	
	$col = $rpg->addColumn("شماره سند", "LocalNo", "PrintDocRender");
	$col->ExcelRender = false;
	$rpg->addColumn("کد حساب", "CostCode");
	$rpg->addColumn("شرح حساب", "CostDesc");
	$rpg->addColumn("تفصیلی", "TafsiliDesc");
	$rpg->addColumn("تفصیلی2", "TafsiliDesc2");
	$rpg->addColumn("تفصیلی3", "TafsiliDesc3");
	$rpg->addColumn("آیتم1", "ParamValue1");
	$rpg->addColumn("آیتم2", "ParamValue2");
	$rpg->addColumn("آیتم3", "ParamValue3");
	$rpg->addColumn("تاریخ سند", "DocDate","ReportDateRender");
	$rpg->addColumn("شرح", "detail");	
	
	$col = $rpg->addColumn("مبلغ بدهکار", "DebtorAmount", "ReportMoneyRender");
	$col->EnableSummary();
	$col = $rpg->addColumn("مبلغ بستانکار", "CreditorAmount", "ReportMoneyRender");
	$col->EnableSummary();

	$col = $rpg->addColumn("مانده حساب", "CreditorAmount", "TotalRemainRender");
	$col->ExcelRender = true;
	
	$rpg->mysql_resource = $dataTable;
	$rpg->page_size = 18;
	$rpg->paging = true;
	
	if(!$rpg->excel && !$IsDashboard)
	{
		BeginReport();
		
		//if($_SESSION["USER"]["UserName"] == "admin")
		//	echo PdoDataAccess::GetLatestQueryString ();
		
		$dt = PdoDataAccess::runquery("select * from BSC_branches");
		$branches = array();
		foreach($dt as $row)
			$branches[ $row["BranchID"] ] = $row["BranchName"];
		
		echo
		"<table style='border:2px groove #9BB1CD;border-collapse:collapse;width:100%'><tr>
				<td width=60px><img src='/framework/icons/logo.jpg' style='width:120px'></td>
				<td align='center' style='height:100px;vertical-align:middle;font-family:titr;font-size:15px'>
					گزارش گردش حساب ها 
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

	/*$rpg->SubHeaderFunction = "RemainRender";
	function RemainRender($PageNo)
	{
		global $BeforeRemaindar;
		if($PageNo == 1)
		echo $BeforeRemaindar;
	}*/

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
AccReport_flow.prototype = {
	TabID : '<?= $_REQUEST["ExtTabID"]?>',
	address_prefix : "<?= $js_prefix_address?>",

	get : function(elementID){
		return findChild(this.TabID, elementID);
	}
}

AccReport_flow.prototype.showReport = function(btn, e)
{
	this.form = this.get("mainForm")
	this.form.target = "_blank";
	this.form.method = "POST";
	this.form.action =  this.address_prefix + "flow.php?show=true";
	this.form.submit();
	this.get("excel").value = "";
	return;
}

function AccReport_flow()
{
	this.blockTpl = new Ext.XTemplate(
		'<table cellspacing="0" width="100%"><tr class="x-grid-header-ct">'
		,'<td>کد</td><td>عنوان</td>'
		,'<tpl for=".">'
		,'<tr class="x-boundlist-item" style="border-left:0;border-right:0">'
		,'<td style="border-left:0;border-right:0" class="search-item">{BlockCode}</td>'
		,'<td style="border-left:0;border-right:0" class="search-item">{BlockDesc}</td>'
		,'</tpl>'
		,'</table>');
		
	this.formPanel = new Ext.form.Panel({
		renderTo : this.get("main"),
		frame : true,
		layout :{
			type : "table",
			columns :2
		},
		bodyStyle : "text-align:right;padding:5px",
		title : "گزارش گردش حساب ها",
		defaults : {
			labelWidth :100,
			width : 270
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
					url: "/accounting/global/domain.data.php?task=SelectCycles",
					reader: {root: 'rows',totalProperty: 'totalCount'}
				},
				fields : ['CycleID','CycleDesc'],
				autoLoad : true					
			}),
			fieldLabel : "دوره",
			queryMode : 'local',
			value : "<?= !isset($_SESSION["accounting"]["CycleID"]) ? "" : $_SESSION["accounting"]["CycleID"] ?>",
			displayField : "CycleDesc",
			valueField : "CycleID",
			hiddenName : "CycleID"
		},{
			xtype : "combo",
			displayField : "BlockDesc",
			fieldLabel : "گروه حساب",
			valueField : "BlockID",
			itemId : "cmp_level0",
			hiddenName : "GroupID",
			queryMode : 'local',
			store : new Ext.data.Store({
				fields:["BlockID","BlockCode","BlockDesc"],
				proxy: {
					type: 'jsonp',
					url: this.address_prefix + '../baseinfo/baseinfo.data.php?task=SelectBlocks&level=0',
					reader: {root: 'rows',totalProperty: 'totalCount'}
				},
				autoLoad : true
			}),
			tpl: this.blockTpl
		},{
			xtype : "combo",
			displayField : "BlockDesc",
			fieldLabel : "کل",
			valueField : "BlockID",
			itemId : "cmp_level1",
			hiddenName : "level1",
			store : new Ext.data.Store({
				fields:["BlockID","BlockCode","BlockDesc"],
				proxy: {
					type: 'jsonp',
					url: this.address_prefix + '../baseinfo/baseinfo.data.php?task=SelectBlocks&level=1',
					reader: {root: 'rows',totalProperty: 'totalCount'}
				},
				autoLoad : true,
				PageSize : 20
			}),
			tpl: this.blockTpl,
			PageSize : 20,
			listeners : {
				select : function(combo,records){
					AccReport_flowObj.formPanel.down("[hiddenName=level2]").getStore().load({
						params : {
							PreLevel : records[0].data.BlockID
						}
					});
				}
			}
		},{
			xtype : "combo",
			displayField : "BlockDesc",
			fieldLabel : "معین1",
			valueField : "BlockID",
			itemId : "cmp_level2",
			queryMode : "local",
			hiddenName : "level2",
			store : new Ext.data.Store({
				fields:["BlockID","BlockCode","BlockDesc"],
				proxy: {
					type: 'jsonp',
					url: this.address_prefix + '../baseinfo/baseinfo.data.php?task=SelectBlocks&level=2',
					reader: {root: 'rows',totalProperty: 'totalCount'}
				},
				autoLoad : true
			}),
			tpl: this.blockTpl,
			listeners : {
				select : function(combo,records){
					AccReport_flowObj.formPanel.down("[hiddenName=level3]").getStore().load({
						params : {
							PreLevel : records[0].data.BlockID
						}
					});
				}
			}
		},{
			xtype : "combo",
			displayField : "BlockDesc",
			fieldLabel : "معین2",
			valueField : "BlockID",
			itemId : "cmp_level3",
			queryMode : "local",
			hiddenName : "level3",
			store : new Ext.data.Store({
				fields:["BlockID","BlockCode","BlockDesc"],
				proxy: {
					type: 'jsonp',
					url: this.address_prefix + '../baseinfo/baseinfo.data.php?task=SelectBlocks&level=3',
					reader: {root: 'rows',totalProperty: 'totalCount'}
				},
				autoLoad : true
			}),
			tpl: this.blockTpl
		},{
			xtype : "combo",
			colspan : 2,
			displayField : "BlockDesc",
			fieldLabel : "معین3",
			valueField : "BlockID",
			itemId : "cmp_level4",
			queryMode : "local",
			hiddenName : "level4",
			store : new Ext.data.Store({
				fields:["BlockID","BlockCode","BlockDesc"],
				proxy: {
					type: 'jsonp',
					url: this.address_prefix + '../baseinfo/baseinfo.data.php?task=SelectBlocks&level=4',
					reader: {root: 'rows',totalProperty: 'totalCount'}
				},
				autoLoad : true
			}),
			tpl: this.blockTpl
		},{
			xtype : "container",
			colspan : 2,
			width : 720,
			html : "<hr style='border-top: 1px solid #999;border-bottom: 0;'>"
		},{
			xtype : "combo",
			displayField : "InfoDesc",
			fieldLabel : "گروه تفصیلی",
			valueField : "InfoID",
			hiddenName : "TafsiliGroup",
			queryMode : 'local',
			store : new Ext.data.Store({
				fields:['InfoID','InfoDesc'],
				proxy: {
					type: 'jsonp',
					url: this.address_prefix + '../baseinfo/baseinfo.data.php?task=SelectTafsiliGroups',
					reader: {root: 'rows',totalProperty: 'totalCount'}
				},
				autoLoad : true
			}),
			listeners : {
				select : function(combo,records){
					el = AccReport_flowObj.formPanel.down("[itemId=cmp_tafsiliID]");
					el.setValue();
					el.enable();
					el.getStore().proxy.extraParams["TafsiliType"] = this.getValue();
					el.getStore().load();
				}
			}
		},{
			xtype : "combo",
			displayField : "TafsiliDesc",
			fieldLabel : "تفصیلی",
			disabled : true,
			valueField : "TafsiliID",
			itemId : "cmp_tafsiliID",
			hiddenName : "TafsiliID",
			store : new Ext.data.Store({
				fields:["TafsiliID","TafsiliDesc"],
				proxy: {
					type: 'jsonp',
					url: this.address_prefix + '../baseinfo/baseinfo.data.php?task=GetAllTafsilis',
					reader: {root: 'rows',totalProperty: 'totalCount'}
				}
			})
		},{
			xtype : "combo",
			displayField : "InfoDesc",
			fieldLabel : "گروه تفصیلی2",
			valueField : "InfoID",
			hiddenName : "TafsiliGroup2",
			queryMode : 'local',
			store : new Ext.data.Store({
				fields:['InfoID','InfoDesc'],
				proxy: {
					type: 'jsonp',
					url: this.address_prefix + '../baseinfo/baseinfo.data.php?task=SelectTafsiliGroups',
					reader: {root: 'rows',totalProperty: 'totalCount'}
				},
				autoLoad : true
			}),
			listeners : {
				select : function(combo,records){
					el = AccReport_flowObj.formPanel.down("[itemId=cmp_tafsiliID2]");
					el.setValue();
					el.enable();
					el.getStore().proxy.extraParams["TafsiliType"] = this.getValue();
					el.getStore().load();
				}
			}
		},{
			xtype : "combo",
			displayField : "TafsiliDesc",
			fieldLabel : "تفصیلی",
			disabled : true,
			valueField : "TafsiliID",
			itemId : "cmp_tafsiliID2",
			hiddenName : "TafsiliID2",
			store : new Ext.data.Store({
				fields:["TafsiliID","TafsiliDesc"],
				proxy: {
					type: 'jsonp',
					url: this.address_prefix + '../baseinfo/baseinfo.data.php?task=GetAllTafsilis',
					reader: {root: 'rows',totalProperty: 'totalCount'}
				}
			})
		},{
			xtype : "combo",
			displayField : "InfoDesc",
			fieldLabel : "گروه تفصیلی3",
			valueField : "InfoID",
			hiddenName : "TafsiliGroup3",
			queryMode : 'local',
			store : new Ext.data.Store({
				fields:['InfoID','InfoDesc'],
				proxy: {
					type: 'jsonp',
					url: this.address_prefix + '../baseinfo/baseinfo.data.php?task=SelectTafsiliGroups',
					reader: {root: 'rows',totalProperty: 'totalCount'}
				},
				autoLoad : true
			}),
			listeners : {
				select : function(combo,records){
					el = AccReport_flowObj.formPanel.down("[itemId=cmp_tafsiliID3]");
					el.setValue();
					el.enable();
					el.getStore().proxy.extraParams["TafsiliType"] = this.getValue();
					el.getStore().load();
				}
			}
		},{
			xtype : "combo",
			displayField : "TafsiliDesc",
			fieldLabel : "تفصیلی",
			disabled : true,
			valueField : "TafsiliID",
			itemId : "cmp_tafsiliID3",
			hiddenName : "TafsiliID3",
			store : new Ext.data.Store({
				fields:["TafsiliID","TafsiliDesc"],
				proxy: {
					type: 'jsonp',
					url: this.address_prefix + '../baseinfo/baseinfo.data.php?task=GetAllTafsilis',
					reader: {root: 'rows',totalProperty: 'totalCount'}
				}
			})
		},{
			xtype : "container",
			colspan : 2,
			width : 720,
			html : "<hr style='border-top: 1px solid #999;border-bottom: 0;'>"
		},{
			colspan : 2,
			xtype : "combo",
			store : new Ext.data.SimpleStore({
				proxy: {
					type: 'jsonp',
					url: this.address_prefix + '../../framework/person/persons.data.php?' +
						"task=selectPersons",
					reader: {root: 'rows',totalProperty: 'totalCount'}
				},
				fields : ['PersonID','fullname']
			}),
			fieldLabel : "ذینفع",
			pageSize : 25,
			width : 400,
			displayField : "fullname",
			valueField : "PersonID",
			hiddenName : "PersonID"
		},{
			xtype : "container",
			colspan : 2,
			width : 720,
			html : "<hr style='border-top: 1px solid #999;border-bottom: 0;'>"
		},{
			xtype : "numberfield",
			hideTrigger : true,
			name : "fromLocalNo",
			fieldLabel : "از سند شماره"
		},{
			xtype : "numberfield",
			hideTrigger : true,
			name : "toLocalNo",
			fieldLabel : "تا سند شماره"
		},{
			xtype : "shdatefield",
			name : "fromDate",
			fieldLabel : "تاریخ سند از"
		},{
			xtype : "shdatefield",
			name : "toDate",
			fieldLabel : "تا تاریخ"
		},{
			xtype : "textfield",
			name : "description",
			fieldLabel : "شرح سند"
		},{
			xtype : "textfield",
			name : "details",
			fieldLabel : "جزئیات ردیف"
		},{
			xtype : "fieldset",
			title : "تنظیمات آیتمها",
			height : 330,
			width : 300,
			rowspan : 3,
			autoScroll : true,
			itemId : "FS_params"
		},{
			xtype : "container",
			html : "<input type=checkbox checked name=IncludeRaw> گزارش شامل اسناد پیش نویس نیز باشد"
		},{
			xtype : "container",
			html : "<input type=checkbox name=IncludeStart> گزارش شامل اسناد افتتاحیه باشد"
		},{
			xtype : "container",
			html : "<input type=checkbox name=IncludeEnd> گزارش شامل اسناد اختتامیه باشد"
		},{
			xtype : "fieldset",
			title : "ستونهای گزارش",
			colspan :2,
			width : 730,
			items :[<?= $page_rpg->ReportColumns() ?>]
		},{
			xtype : "fieldset",
			colspan :2,
			width : 730,
			title : "رسم نمودار",
			items : [<?= $page_rpg->GetChartItems("AccReport_flowObj","mainForm","flow.php") ?>]
		}],
		buttons : [{
			text : "گزارش ساز",
			iconCls : "db",
			handler : function(){ReportGenerator.ShowReportDB(
						AccReport_flowObj, 
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
					AccReport_flowObj.get('excel').value = "true";
				}
			},
			iconCls : "excel"
		},{
			text : "پاک کردن گزارش",
			iconCls : "clear",
			handler : function(){
				AccReport_flowObj.formPanel.getForm().reset();
				AccReport_flowObj.get("mainForm").reset();
			}			
		}]
	});
	
	paramsStore = new Ext.data.SimpleStore({
		fields:["ParamID","ParamDesc","ParamType"],
		proxy: {
			type: 'jsonp',
			url: this.address_prefix + '../docs/doc.data.php?task=selectAllParams',
			reader: {root: 'rows',totalProperty: 'totalCount'}
		},
		autoLoad: true,
		listeners : {
			load : function(){
				var ParamsFS = AccReport_flowObj.formPanel.down("[itemId=FS_params]");
				for(i=0; i< this.totalCount; i++)
				{
					record = this.getAt(i);
					if(record.data.ParamType == "combo")
					{
						ParamsFS.add({
							xtype : "combo",
							hiddenName : "paramID" + record.data.ParamID,
							fieldLabel : record.data.ParamDesc,
							store : new Ext.data.Store({
								fields:["id","title"],
								proxy: {
									type: 'jsonp',
									url: AccReport_flowObj.address_prefix + 
										'../docs/doc.data.php?task=selectParamItems&ParamID=' +
										record.data.ParamID,
									reader: {root: 'rows',totalProperty: 'totalCount'}
								},
								autoLoad: true
							}),
							valueField : "id",
							displayField : "title"
						});							
					}
					else
					{
						ParamsFS.add({
							xtype : record.data.ParamType,
							name : "paramID" + record.data.ParamID,
							fieldLabel : record.data.ParamDesc,
							hideTrigger : (record.data.ParamType == "numberfield" || 
								record.data.ParamType == "currencyfield" ? true : false)
						});			
					}
				}
			}
		}
	});
}

AccReport_flowObj = new AccReport_flow();
</script>
<form id="mainForm">
	<center><br>
		<div id="main" ></div>
	</center>
	<input type="hidden" name="excel" id="excel">
</form>
