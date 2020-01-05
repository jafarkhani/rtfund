<?php
//-----------------------------
//	Programmer	: SH.Jafarkhani
//	Date		: 94.06
//-----------------------------

require_once '../header.inc.php';
require_once "ReportGenerator.class.php";

function bdremainRender($row){
	$v = $row["bdAmount"] - $row["bsAmount"];
	return $v < 0 ? 0 : number_format($v);
}
function bsremainRender($row){
	$v = $row["bsAmount"] - $row["bdAmount"];
	return $v < 0 ? 0 : number_format($v);
}
function remainRender($row){
	return $row["bsAmount"]*1 - $row["bdAmount"]*1;
}

$page_rpg = new ReportGenerator("mainForm","AccReport_tarazObj");
$page_rpg->addColumn("گروه حساب", "CostGroupDesc");
$page_rpg->addColumn("گروه", "level0Desc");
$page_rpg->addColumn("کل", "level1Desc");
$page_rpg->addColumn("معین1", "level2Desc");
$page_rpg->addColumn("معین2", "level3Desc");
$page_rpg->addColumn("معین3", "level4Desc");
$page_rpg->addColumn("تفصیلی", "TafsiliDesc");
$page_rpg->addColumn("تفصیلی2", "TafsiliDesc2");
$page_rpg->addColumn("گردش بدهکار", "bdAmount" , "ReportMoneyRender");
$page_rpg->addColumn("گردش بستانکار", "bsAmount", "ReportMoneyRender");
$page_rpg->addColumn("مانده بدهکار", "bdAmount", "bdremainRender");
$page_rpg->addColumn("مانده بستانکار", "bsAmount", "bsremainRender");
$page_rpg->addColumn("مانده حساب", "bsAmount", "remainRender");

global $level;

function GetData(&$rpg){
	
	global $level;
	$level = empty($_REQUEST["level"]) ? "l1" : $_REQUEST["level"];
	
	$IncludeFirstCycle = $_REQUEST["resultColumns"]*1 >= 6;
	
	$query = "select 
		di.CostID,
		sum(di.DebtorAmount) DebtorAmount,
		sum(di.CreditorAmount) CreditorAmount," . 
			
		($IncludeFirstCycle ? 
		"(tdt.StartCycleDebtor) StartDebtorAmount,
		(tdt.StartCycleCreditor) StartCreditorAmount," : "" ) . " 

		b0.BlockDesc level0Desc,
		b1.BlockDesc level1Desc,
		b2.BlockDesc level2Desc,
		b3.BlockDesc level3Desc,
		b4.BlockDesc level4Desc,
		
		b0.BlockCode BlockCode0,
		b1.BlockCode BlockCode1,
		b2.BlockCode BlockCode2,
		b3.BlockCode BlockCode3,
		b4.BlockCode BlockCode4,
		
		b0.BlockCode level0Code,
		b1.BlockCode level1Code,
		ifnull(b2.BlockCode,'00') level2Code,
		ifnull(b3.BlockCode,'00') level3Code,
		ifnull(b4.BlockCode,'00') level4Code,
		
		t.TafsiliDesc TafsiliDesc,
		t.TafsiliCode TafsiliCode,
		t2.TafsiliDesc TafsiliDesc2,
		t2.TafsiliCode TafsiliCode2,
		t3.TafsiliDesc TafsiliDesc3,
		t3.TafsiliCode TafsiliCode3,
				
		b1.GroupID as level0,
		cc.level1,
		cc.level2,
		cc.level3,
		cc.level4,
		cc.param1 CostParam1,
		cc.param2 CostParam2,
		cc.param3 CostParam3,
		
		di.TafsiliType,
		di.TafsiliType2,
		di.TafsiliID,
		di.TafsiliID2,
		di.TafsiliID3,
		
		di.param1,
		di.param2,
		di.param3,

		p1.paramDesc paramDesc1,
		p2.paramDesc paramDesc2,
		p3.paramDesc paramDesc3
		
		from ACC_DocItems di 
			join ACC_docs d using(DocID)
			join ACC_CostCodes cc using(CostID)
			join ACC_blocks b1 on(level1=b1.BlockID)
			left join ACC_blocks b0 on(b1.GroupID=b0.BlockID)
			left join ACC_blocks b2 on(level2=b2.BlockID)
			left join ACC_blocks b3 on(level3=b3.BlockID)
			left join ACC_blocks b4 on(level4=b4.BlockID)
			
			left join ACC_tafsilis t using(TafsiliID)
			left join ACC_tafsilis t2 on(t2.TafsiliID=di.TafsiliID2)
			left join ACC_tafsilis t3 on(t3.TafsiliID=di.TafsiliID3)
			
			left join ACC_CostCodeParams p1 on(p1.ParamID=cc.param1)
			left join ACC_CostCodeParams p2 on(p2.ParamID=cc.param2)
			left join ACC_CostCodeParams p3 on(p3.ParamID=cc.param3)" . 

			($IncludeFirstCycle ? 
			"left join (
				select CycleID,CostID,di.TafsiliID,di.TafsiliID2,di.TafsiliID3,di.param1,di.param2,di.param3,
						sum(DebtorAmount) StartCycleDebtor,
						sum(CreditorAmount) StartCycleCreditor
				from ACC_DocItems di join ACC_docs using(DocID)
				where DocType=1 " .
				(!empty($_POST["CycleID"]) ? " AND CycleID=:c" : "") . 
				(!empty($_POST["BranchID"]) ? " AND BranchID=:b" : "") . "	
				group by di.CostID,di.TafsiliID,di.TafsiliID2,di.TafsiliID3,di.param1,di.param2,di.param3
			)tdt on(d.CycleID=tdt.CycleID AND di.CostID=tdt.CostID 
					AND di.TafsiliID=tdt.TafsiliID 
					AND di.TafsiliID2=tdt.TafsiliID2
					AND di.TafsiliID3=tdt.TafsiliID3
					AND di.param1=tdt.param1
					AND di.param2=tdt.param2
					AND di.param3=tdt.param3)" : "");
	$group = "";
	if($level >= "l0")
	{
		$col = $rpg->addColumn("کد گروه", "level0Code");
		$col = $rpg->addColumn("گروه", "level0Desc",$level =="l0" ? "levelRender" : "");
		$group = "tbl.GroupID";
		$col->ExcelRender = false;
	}
	if($level >= "l1")
	{
		$col = $rpg->addColumn("کد کل", "level1Code");
		$col = $rpg->addColumn("کل", "level1Desc",$level =="l1" ? "levelRender" : "");
		$col->ExcelRender = false;
		$group = "tbl.level1";
	}
	if($level >= "l2")
	{
		$group .= ",tbl.level2"; 
		$col = $rpg->addColumn("کد معین1", "level2Code");
		$col = $rpg->addColumn("معین1", "level2Desc", $level =="l2" ? "levelRender" : "");
		$col->ExcelRender = false;
	}
	if($level >= "l3")
	{
		$group .= ",tbl.level3"; 
		$col = $rpg->addColumn("کد معین2", "level3Code");
		$col = $rpg->addColumn("معین2", "level3Desc", $level =="l3" ? "levelRender" : "");
		$col->ExcelRender = false;
	}
	if($level >= "l4")
	{
		$group .= ",tbl.level4"; 
		$col = $rpg->addColumn("کد معین3", "level4Code");
		$col = $rpg->addColumn("معین3", "level4Desc", $level =="l4" ? "levelRender" : "");
		$col->ExcelRender = false;
	}
	if($level == "l5")
	{
		/*function uniqueRender($row,$val){
			return $row["CostID"] . "-" . ($row["TafsiliID"] == "" ? 0 :$row["TafsiliID"]) . "-" . 
					($row["TafsiliID2"] == "" ? 0 :$row["TafsiliID2"]);
		}
		$col = $rpg->addColumn("کد یکتا", "CostID", "uniqueRender");*/
		
		$group .= ",tbl.TafsiliID";
		$col = $rpg->addColumn("کد تفصیلی1", "TafsiliID");
		$col = $rpg->addColumn("تفصیلی1", "TafsiliDesc", "levelRender");
		$col->ExcelRender = false;
		
		$group .= ",tbl.TafsiliID2";
		$col = $rpg->addColumn("کد تفصیلی2", "TafsiliID2");
		$col = $rpg->addColumn("تفصیلی2", "TafsiliDesc2", "levelRender");
		$col->ExcelRender = false;
		
		$group .= ",tbl.TafsiliID3";
		$col = $rpg->addColumn("کد تفصیلی3", "TafsiliCode3");
		$col = $rpg->addColumn("تفصیلی3", "TafsiliDesc3", "levelRender");
		$col->ExcelRender = false;
	}
	if($level == "l6")
	{
		$group .= ",tbl.TafsiliID2";
		$col = $rpg->addColumn("کد تفصیلی2", "TafsiliID2");
		$col = $rpg->addColumn("تفصیلی2", "TafsiliDesc2", "levelRender");
		$col->ExcelRender = false;
	}
	if($level == "l7")
	{
		$group .= ",tbl.TafsiliID3";
		$col = $rpg->addColumn("کد تفصیلی3", "TafsiliCode3");
		$col = $rpg->addColumn("تفصیلی3", "TafsiliDesc3", "levelRender");
		$col->ExcelRender = false;
	}
	if($level == "l8")
	{
		$group .= ",tbl.param1";
		$col = $rpg->addColumn("عنوان آیتم1", "paramDesc1");
		$col = $rpg->addColumn("آیتم1", "param1", "levelRender");
		$col->ExcelRender = false;
	}
	if($level == "l9")
	{
		$group .= ",tbl.param2";
		$col = $rpg->addColumn("عنوان آیتم2", "paramDesc2");
		$col = $rpg->addColumn("آیتم2", "param2", "levelRender");
		$col->ExcelRender = false;
	}
	if($level == "l10")
	{
		$group .= ",tbl.param3";
		$col = $rpg->addColumn("عنوان آیتم3", "paramDesc3");
		$col = $rpg->addColumn("آیتم3", "param3", "levelRender");
		$col->ExcelRender = false;
	}
		
	function levelRender($row, $value){
		
		global $level;
		
		if($value == "")
			$value = "-----";
		
		return "<a onclick=changeLevel('" . $level . "','".
				($level >= "l0" ? $row["level0"] : "") . "','" . 
				($level >= "l1" ? $row["level1"] : "") . "','" . 
				($level >= "l2" ? $row["level2"] : "") . "','" . 
				($level >= "l3" ? $row["level3"] : ""). "','" . 
				($level >= "l4" ? $row["level4"] : ""). "','" . 
				($level >= "l5" ? $row["TafsiliID"]. "','".$row["TafsiliID2"]. "','".$row["TafsiliID3"] : ""). "') "." 
				href='javascript:void(0);'>" . $value . "</a>";
	}
	
	function MakeWhere(&$where, &$whereParam){
 
		if(session::IsPortal() && isset($_REQUEST["dashboard_show"]))
		{
			$where .= " AND (t.TafsiliType=".TAFSILITYPE_PERSON." AND t.ObjectID=" . $_SESSION["USER"]["PersonID"] .
				" OR t2.TafsiliType=".TAFSILITYPE_PERSON." AND t2.ObjectID=" . $_SESSION["USER"]["PersonID"] . ")";
		}
		if(empty($_POST["IncludeRaw"]))
		{
			$where .= " AND d.StatusID != " . ACC_STEPID_RAW;
		}
		if(empty($_REQUEST["IncludeStart"]))
		{
			$where .= " AND d.DocType != " . DOCTYPE_STARTCYCLE;
		}
		if(empty($_REQUEST["IncludeEnd"]))
		{
			$where .= " AND d.DocType not in(" . DOCTYPE_ENDCYCLE . "," . DOCTYPE_CLOSECYCLE . ")";
		}
		if(!empty($_REQUEST["CostGroupID"]))
		{
			$where .= " AND cc.CostGroupID=:ccid";
			$whereParam[":ccid"] = $_REQUEST["CostGroupID"];
		}
		if(!empty($_REQUEST["EventID"]))
		{
			$where .= " AND d.EventID in (" . $_REQUEST["EventID"] . ")";
		}
		if(isset($_REQUEST["level0"]))
		{
			$where .= " AND b1.GroupID=:l0";
			$whereParam[":l0"] = $_REQUEST["level0"];
		}
		if(isset($_REQUEST["level1"]))
		{
			if($_REQUEST["level1"] == "")
				$where .= " AND cc.level1 is null";
			else
			{
				$where .= " AND cc.level1=:l1";
				$whereParam[":l1"] = $_REQUEST["level1"];
			}
		}
		if(isset($_REQUEST["level2"]))
		{
			if($_REQUEST["level2"] == "")
				$where .= " AND cc.level2 is null";
			else
			{
				$where .= " AND cc.level2=:l2";
				$whereParam[":l2"] = $_REQUEST["level2"];
			}
		}
		if(isset($_REQUEST["level3"]))
		{
			if($_REQUEST["level3"] == "")
				$where .= " AND cc.level3 is null";
			else
			{
				$where .= " AND cc.level3=:l3";
				$whereParam[":l3"] = $_REQUEST["level3"];
			}
		}
		if(isset($_REQUEST["level4"]))
		{
			if($_REQUEST["level4"] == "")
				$where .= " AND cc.level4 is null";
			else
			{
				$where .= " AND cc.level4=:l4";
				$whereParam[":l4"] = $_REQUEST["level4"];
			}
		}
		//..............................................
		if(!empty($_POST["level0s"]))
		{
			$level0s = preg_replace("/[^0-9,]+/", "", $_POST["level0s"]);
            $where .= " AND b1.GroupID in( " . $level0s . ")";
		}
		//----------------------------------------------
		if(!empty($_POST["level1s"]))
		{
			$level1s = preg_replace("/[^0-9,]+/", "", $_POST["level1s"]);
            $where .= " AND cc.level1 in( " . $level1s . ")";
		}
		//----------------------------------------------
		if(!empty($_POST["level2s"]))
		{
			$level2s = preg_replace("/[^0-9,]+/", "", $_POST["level2s"]);
			$level2s = trim($level2s, ",");
            $where .= " AND cc.level2 in( " . $level2s . ")";
		}
		//----------------------------------------------
		if(!empty($_POST["level3s"]))
		{
			$level3s = preg_replace("/[^0-9,]+/", "", $_POST["level3s"]);
			$level3s = substr($level3s, 0, strlen($level3s)-1);
            $where .= " AND cc.level3 in( " . $level3s . ")";
		}
		//----------------------------------------------
		if(!empty($_POST["TafsiliGroup"]))
		{
			$where .= " AND di.TafsiliType=:tt";
			$whereParam[":tt"] = $_POST["TafsiliGroup"];
		}
		if(!empty($_POST["TafsiliGroup2"]))
		{
			$where .= " AND di.TafsiliType2=:tt2";
			$whereParam[":tt2"] = $_POST["TafsiliGroup2"];
		}
		if(!empty($_POST["TafsiliID"]))
		{
			$where .= " AND di.TafsiliID=:tid";
			$whereParam[":tid"] = $_POST["TafsiliID"];
		}
		if(!empty($_POST["TafsiliID2"]))
		{
			$where .= " AND di.TafsiliID2=:tid2";
			$whereParam[":tid2"] = $_POST["TafsiliID2"];
		}
		if(!empty($_POST["TafsiliID3"]))
		{
			$where .= " AND di.TafsiliID3=:tid3";
			$whereParam[":tid3"] = $_POST["TafsiliID3"];
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
		if(!empty($_POST["fromDate"]))
		{
			$where .= " AND d.DocDate >= :q1 ";
			$whereParam[":q1"] = DateModules::shamsi_to_miladi($_POST["fromDate"], "-");
		}
		if(!empty($_POST["toDate"]))
		{
			$where .= " AND d.DocDate <= :q2 ";
			$whereParam[":q2"] = DateModules::shamsi_to_miladi($_POST["toDate"], "-");
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
	}
		
	$where = "";
	$whereParam = array();
	
	if(!empty($_POST["BranchID"]))
	{
		$where .= " AND d.BranchID=:b";
		$whereParam[":b"] = $_POST["BranchID"];
	}	
	if(!empty($_POST["CycleID"]))
	{
		$where .= " AND d.CycleID=:c";
		$whereParam[":c"] = $_POST["CycleID"];
	}	
	
	MakeWhere($where, $whereParam);
	
	$query = "select tbl.*,
					sum(DebtorAmount) bdAmount,
					sum(CreditorAmount) bsAmount" . 
					($IncludeFirstCycle ? ",
					sum(StartDebtorAmount) StartCycleDebtor,
					sum(StartCreditorAmount)StartCycleCreditor" : "") . " 
			from ( " .
			$query . "
			where 1=1 " . $where . " 
			group by di.CostID,di.TafsiliID,di.TafsiliID2)tbl			
	";
	$query .= $group != "" ? " group by " . $group : "";
	
	//------------ make having ----------------
	if(!empty($_REQUEST["RemainOnly"]))
	{
		$query .= " having bdAmount<>bsAmount ";
	}
	//-----------------------------------------
	
	$query .= " order by tbl.BlockCode1,tbl.BlockCode2,tbl.BlockCode3,tbl.BlockCode4,tbl.TafsiliID,tbl.TafsiliID2";

	$dt = PdoDataAccess::runquery($query, $whereParam);
	if($_SESSION["USER"]["UserName"] == "admin")
	{
		BeginReport();
		print_r(ExceptionHandler::PopAllExceptions());
		echo PdoDataAccess::GetLatestQueryString ();
	}
	return $dt;
}

function ListData($IsDashboard = false){
	
	global $level;
	$levelsDescArr = array(
		"l0" => "گروه",
		"l1" => "کل",
		"l2" => "معین1",
		"l3" => "معین2",
		"l4" => "معین3",
		"l5" => "تفصیلی",
		"l6" => "تفصیلی2",
		"l7" => "تفصیلی3",
		"l8" => "آیتم1",
		"l9" => "آیتم2",
		"l10" => "آیتم3"
		);
	$dt = PdoDataAccess::runquery("select * from BSC_branches");
	$branches = array();
	foreach($dt as $row)
		$branches[ $row["BranchID"] ] = $row["BranchName"];
			
	
	$rpg = new ReportGenerator();
	$rpg->excel = !empty($_POST["excel"]);
	
	$dataTable = GetData($rpg);
	
	$redirect = "";
	if(count($dataTable) == 1 && !$IsDashboard)
	{
		$LevelValue = "";
		switch($level)
		{
			case "l2" : $LevelValue = $dataTable[0]["level2"];	break;
			case "l3" : $LevelValue = $dataTable[0]["level3"];	break;
			case "l4" : $LevelValue = $dataTable[0]["level4"];	break;
		}
		if($LevelValue == "" && $level >= "l2" && $level <= "l4")
		{
			$row = $dataTable[0];
			$redirect =  "<script>changeLevel('" . $level . "','".
			($level >= "l0" ? $row["level0"] : "") . "','" . 
			($level >= "l1" ? $row["level1"] : "") . "','" . 
			($level >= "l2" ? $row["level2"] : "") . "','" . 
			($level >= "l3" ? $row["level3"] : ""). "','" . 
			($level == "l4" ? $row["level4"] : ""). "');</script>";
		}
	}
	//..........................................................................
		
	if($_REQUEST["resultColumns"]*1 >= 6)
	{
		$col = $rpg->addColumn("بدهکار", "StartCycleDebtor", "ReportMoneyRender");
		$col->GroupHeader = "حساب ابتدای دوره";
		$col->EnableSummary(true);
		$col = $rpg->addColumn("بستانکار", "StartCycleCreditor", "ReportMoneyRender");
		$col->GroupHeader = "حساب ابتدای دوره";
		$col->EnableSummary(true);
	}
	if($_REQUEST["resultColumns"]*1 >= 4)
	{
		$col = $rpg->addColumn("بدهکار", "bdAmount" , "ReportMoneyRender");
		$col->GroupHeader = "گردش طی دوره";
		$col->EnableSummary();
		$col = $rpg->addColumn("بستانکار", "bsAmount", "ReportMoneyRender");
		$col->GroupHeader = "گردش طی دوره";
		$col->EnableSummary();
	}
	$col = $rpg->addColumn("مانده بدهکار", "bdAmount", "bdremainRender");
	$col->GroupHeader = "مانده پایان دوره";
	$col->EnableSummary(true);
	$col = $rpg->addColumn("مانده بستانکار", "bsAmount", "bsremainRender");
	$col->GroupHeader = "مانده پایان دوره";
	$col->EnableSummary(true);

	//if($_SESSION["USER"]["UserName"] == "admin")
	//	echo PdoDataAccess::GetLatestQueryString ();
	
	if(!$rpg->excel && !$IsDashboard)
	{
		BeginReport();
		echo "<table style='border:2px groove #9BB1CD;border-collapse:collapse;width:100%'><tr>
				<td width=60px><img src='/framework/icons/logo.jpg' style='width:120px'></td>
				<td align='center' style='height:100px;vertical-align:middle;font-family:titr;font-size:15px'>
					تراز دفتر 
				".$levelsDescArr[$level]. 
				" <br> ".
				( empty($_POST["BranchID"]) ? "کلیه شعبه ها" : $branches[$_POST["BranchID"]]) .
				"<br>" . 
				( empty($_POST["CycleID"]) ? "کلیه دوره ها" : "دوره سال " . $_POST["CycleID"]) .
				"</td>
				<td width='200px' align='center' style='font-family:tahoma;font-size:11px'>تاریخ تهیه گزارش : " 
			. DateModules::shNow() . "<br>";
		if(!empty($_POST["fromDate"]))
		{
			echo "<br> گزارش از تاریخ : " . $_POST["fromDate"];
		}
		if(!empty($_POST["toDate"]))
		{
			echo "<br> گزارش تا تاریخ: " . $_POST["toDate"] ;
		}
		echo "</td></tr></table>";
	}
	
	$rpg->mysql_resource = $dataTable;
	if($IsDashboard)
	{
		echo "<div style=direction:rtl;padding-right:10px>";
		$rpg->generateReport();
		echo "</div>";
		die();
	}
	
	$rpg->generateReport();
	?>
	<script>
		function changeLevel(curlevel,level0,level1,level2,level3,level4,TafsiliID,TafsiliID2,TafsiliID3)
		{
			var form = document.getElementById("subForm");
			if(curlevel >= "l5")
				form.action = "flow.php?show=true&taraz=true";
			else
				form.action = "taraz.php?show=true";
			form.target = "_blank";
			while (form.firstChild) {
				form.removeChild(form.firstChild);
			}
			
			<?foreach($_POST as $key => $val){?>
				var el = document.createElement("input");
				el.type = "hidden";
				el.name = "<?= $key ?>";
				el.id = "<?= $key ?>";
				el.value = "<?= $val ?>";
				form.append(el);
			<?}?>
			
			nextLevel = (curlevel.substring(1)*1);
			nextLevel = nextLevel+1;
			document.getElementById("level").value = "l" + nextLevel;
			
			if(curlevel >= "l0" && level0 != '')
				form.action += "&level0=" + level0;
			if(curlevel >= "l1" && level1 != '')
				form.action += "&level1=" + level1;
			if(curlevel >= "l2" && level2 != '')
				form.action += "&level2=" + level2;
			if(curlevel >= "l3" && level3 != '')
				form.action += "&level3=" + level3;
			if(curlevel >= "l4" && level4 != '')
				form.action += "&level4=" + level4;
			if(curlevel >= "l5")
			{
				if(TafsiliID != '')
					form.action += "&TafsiliID=" + TafsiliID;
				
				if(TafsiliID != '')
					form.action += "&TafsiliID2=" + TafsiliID2;
				
				if(TafsiliID2 != '')
					form.action += "&TafsiliID3=" + TafsiliID3;
			}
			
			form.submit();
			return;
		}
	</script>
	<form id="subForm" method="POST" target="blank">
	
	</form>
	<?
	echo $redirect;
	die();
}

if(isset($_REQUEST["show"]))
{
	ListData();	
}

if(isset($_REQUEST["rpcmp_chart"]))
{
	$temp = new ReportGenerator();
	$page_rpg->mysql_resource = GetData($temp);
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
	
AccReport_taraz.prototype = {
	TabID : '<?= $_REQUEST["ExtTabID"]?>',
	address_prefix : "<?= $js_prefix_address?>",

	get : function(elementID){
		return findChild(this.TabID, elementID);
	}
}

AccReport_taraz.prototype.showReport = function(btn, e)
{
	this.form = this.get("mainForm")
	this.form.target = "_blank";
	this.form.method = "POST";
	this.form.action =  this.address_prefix + "taraz.php?show=true";
	
	this.form.submit();
	this.get("excel").value = "";
	return;
}

function AccReport_taraz()
{
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
				var ParamsFS = AccReport_tarazObj.formPanel.down("[itemId=FS_params]");
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
									url: AccReport_tarazObj.address_prefix + 
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
	
	
	this.formPanel = new Ext.form.Panel({
		renderTo : this.get("main"),
		frame : true,
		layout :{
			type : "table",
			columns :2
		},
		bodyStyle : "text-align:right;padding:5px",
		title : "گزارش تراز",
		defaults : {
			labelWidth :75,
			width : 400,
			style : "margin-left:15px"
		},
		width : 940,
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
			xtype : "treecombo",
			selectChildren: true,
			canSelectFolders: false,
			multiselect : true, 
			hiddenName : "EventID",
			colspan : 2,
			width : 620,
			fieldLabel: "رویداد مالی",
			store : new Ext.data.TreeStore({
				proxy: {
					type: 'ajax',
					url:  '/commitment/baseinfo/baseinfo.data.php?task=GetEventsTree' 
				},
				root: {
					text: "رویدادهای مالی",
					id: 'src',
					expanded: true
				}
			})
		},{
			xtype : "checkcombo",
			fieldLabel : "گروه حساب",
			valueField : "BlockID",
			displayField : "BlockDesc",
			itemId : "cmp_level0",
			name : "multi_level0",
			hiddenName : "level0s",
			store : new Ext.data.Store({
				fields:["BlockID","BlockCode","BlockDesc"],
				
				proxy: {
					type: 'jsonp',
					url: this.address_prefix + '../baseinfo/baseinfo.data.php?task=SelectBlocks&All=true&level=0',
					reader: {root: 'rows',totalProperty: 'totalCount'}
				},
				autoLoad : true
			})
		},{
			xtype : "checkcombo",
			fieldLabel : "حساب کل",
			name : "multi_level1",
			valueField : "BlockID",
			displayField : "full",
			itemId : "cmp_level1",
			hiddenName : "level1s",
			store : new Ext.data.Store({
				fields:["BlockID","BlockCode","BlockDesc",
					{name : "full", 
					convert: function(v,r){return "[" + r.data.BlockCode + "] " + r.data.BlockDesc }}],
				
				proxy: {
					type: 'jsonp',
					url: this.address_prefix + '../baseinfo/baseinfo.data.php?task=SelectBlocks&All=true&level=1',
					reader: {root: 'rows',totalProperty: 'totalCount'}
				},
				autoLoad : true
			})
		},{
			xtype : "checkcombo",
			fieldLabel : "حساب معین1",
			name : "multi_level2",
			valueField : "BlockID",
			displayField : "full",
			itemId : "cmp_level2",
			hiddenName : "level2s",
			store : new Ext.data.Store({
				fields:["BlockID","BlockCode","BlockDesc",
					{name : "full", 
					convert: function(v,r){return "[" + r.data.BlockCode + "] " + r.data.BlockDesc }}],
				
				proxy: {
					type: 'jsonp',
					url: this.address_prefix + '../baseinfo/baseinfo.data.php?task=SelectBlocks&All=true&level=2',
					reader: {root: 'rows',totalProperty: 'totalCount'}
				},
				autoLoad : true
			})
		},{
			xtype : "checkcombo",
			fieldLabel : "حساب معین2",
			name : "multi_level3",
			valueField : "BlockID",
			displayField : "full",
			itemId : "cmp_level3",
			hiddenName : "level3s",
			store : new Ext.data.Store({
				fields:["BlockID","BlockCode","BlockDesc",
					{name : "full", 
					convert: function(v,r){return "[" + r.data.BlockCode + "] " + r.data.BlockDesc }}],
				
				proxy: {
					type: 'jsonp',
					url: this.address_prefix + '../baseinfo/baseinfo.data.php?task=SelectBlocks&All=true&level=3',
					reader: {root: 'rows',totalProperty: 'totalCount'}
				},
				autoLoad : true
			})
		},{
			xtype : "numberfield",
			hideTrigger : true,
			width : 250,
			name : "fromLocalNo",
			fieldLabel : "از سند شماره"
		},{
			xtype : "numberfield",
			hideTrigger : true,
			width : 250,
			name : "toLocalNo",
			fieldLabel : "تا سند شماره"
		},{
			xtype : "shdatefield",
			name : "fromDate",
			width : 250,
			fieldLabel : "از تاریخ"
		},{
			xtype : "shdatefield",
			name : "toDate",
			width : 250,
			fieldLabel : "تا تاریخ"
		},{
			xtype : "fieldset",
			title : "تنظیمات تفصیلی",
			width : 500,
			layout : {
				type : "table",
				columns : 2
			},	
			defaults : {
				labelWidth :75
			},
			items : [{
				xtype : "combo",
				displayField : "InfoDesc",
				fieldLabel : "گروه تفصیلی1",
				valueField : "InfoID",
				hiddenName : "TafsiliGroup",
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
						el = AccReport_tarazObj.formPanel.down("[itemId=cmp_tafsiliID]");
						el.enable();
						el.setValue();
						el.getStore().proxy.extraParams["TafsiliType"] = this.getValue();
						el.getStore().load();
					}
				}
			},{
				xtype : "combo",
				displayField : "TafsiliDesc",
				fieldLabel : "تفصیلی1",
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
				xtype : "fieldset",
				colspan : 2,
				disabled : true,
				title : "نوع ذینفع",
				layout : "hbox",
				defaults : {style : "margin-right : 20px"},
				items :[{
					xtype : "checkbox",
					boxLabel: 'کارکنان',
					name: 'IsStaff',
					inputValue: 'YES'
				},{
					xtype : "checkbox",
					boxLabel: 'مشتری',
					name: 'IsCustomer',
					inputValue: 'YES'
				},{
					xtype : "checkbox",
					boxLabel: 'سهامدار',
					name: 'IsShareholder',
					inputValue: 'YES'
				},{
					xtype : "checkbox",
					boxLabel: 'سرمایه گذار',
					name: 'IsAgent',
					inputValue: 'YES'
				},{
					xtype : "checkbox",
					boxLabel: 'حامی',
					name: 'IsSupporter',
					inputValue: 'YES'
				},{
					xtype : "checkbox",
					boxLabel: 'کارشناس',
					name: 'IsExpert',
					inputValue: 'YES'
				}]
			},{
				xtype : "combo",
				displayField : "InfoDesc",
				fieldLabel : "گروه تفصیلی2",
				valueField : "InfoID",
				hiddenName : "TafsiliGroup2",
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
						el = AccReport_tarazObj.formPanel.down("[itemId=cmp_tafsiliID2]");
						el.enable();
						el.setValue();
						el.getStore().proxy.extraParams["TafsiliType"] = this.getValue();
						el.getStore().load();
					}
				}
			},{
				xtype : "combo",
				displayField : "TafsiliDesc",
				fieldLabel : "تفصیلی2",
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
				xtype : "fieldset",
				colspan : 2,
				disabled : true,
				title : "نوع ذینفع",
				layout : "hbox",
				defaults : {style : "margin-right : 20px"},
				items :[{
					xtype : "checkbox",
					boxLabel: 'کارکنان',
					name: 'IsStaff',
					inputValue: 'YES'
				},{
					xtype : "checkbox",
					boxLabel: 'مشتری',
					name: 'IsCustomer',
					inputValue: 'YES'
				},{
					xtype : "checkbox",
					boxLabel: 'سهامدار',
					name: 'IsShareholder',
					inputValue: 'YES'
				},{
					xtype : "checkbox",
					boxLabel: 'سرمایه گذار',
					name: 'IsAgent',
					inputValue: 'YES'
				},{
					xtype : "checkbox",
					boxLabel: 'حامی',
					name: 'IsSupporter',
					inputValue: 'YES'
				},{
					xtype : "checkbox",
					boxLabel: 'کارشناس',
					name: 'IsExpert',
					inputValue: 'YES'
				}]
			},{
				xtype : "combo",
				displayField : "InfoDesc",
				fieldLabel : "گروه تفصیلی3",
				valueField : "InfoID",
				hiddenName : "TafsiliGroup3",
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
						el = AccReport_tarazObj.formPanel.down("[itemId=cmp_tafsiliID3]");
						el.enable();
						el.setValue();
						el.getStore().proxy.extraParams["TafsiliType"] = this.getValue();
						el.getStore().load();
					}
				}
			},{
				xtype : "combo",
				displayField : "TafsiliDesc",
				fieldLabel : "تفصیلی3",
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
				xtype : "fieldset",
				colspan : 2,
				disabled : true,
				title : "نوع ذینفع",
				layout : "hbox",
				defaults : {style : "margin-right : 20px"},
				items :[{
					xtype : "checkbox",
					boxLabel: 'کارکنان',
					name: 'IsStaff',
					inputValue: 'YES'
				},{
					xtype : "checkbox",
					boxLabel: 'مشتری',
					name: 'IsCustomer',
					inputValue: 'YES'
				},{
					xtype : "checkbox",
					boxLabel: 'سهامدار',
					name: 'IsShareholder',
					inputValue: 'YES'
				},{
					xtype : "checkbox",
					boxLabel: 'سرمایه گذار',
					name: 'IsAgent',
					inputValue: 'YES'
				},{
					xtype : "checkbox",
					boxLabel: 'حامی',
					name: 'IsSupporter',
					inputValue: 'YES'
				},{
					xtype : "checkbox",
					boxLabel: 'کارشناس',
					name: 'IsExpert',
					inputValue: 'YES'
				}]
			}]
		},{
			xtype : "fieldset",
			title : "تنظیمات آیتمها",
			height : 330,
			autoScroll : true,
			itemId : "FS_params"
		},{
			xtype : "fieldset",
			title : "تنظیمات گزارش",
			width : 920,
			colspan :2,
			defaults : {
				style : "margin-left : 20px"
			},
			layout : "column",
			items : [{
				xtype : "container",
				html :  "تراز بر اساس: "
			},{
				xtype : "container",
				html :  "<input type='radio' name='level' id='level-l0' value='l0' > گروه" + "<br>" +  
					"<input type='radio' name='level' id='level-l1' value='l1' checked> کل " 		
			},{
				xtype : "container",
				html :  "<input type='radio' name='level' id='level-l2' value='l2' > معین  1" + "<br>" +  
					"<input type='radio' name='level' id='level-l3' value='l3' > معین  2" + "<br>" +  
					"<input type='radio' name='level' id='level-l4' value='l4' > معین  3"
			},{
				xtype : "container",
				html :  "<input type='radio' name='level' id='level-l5' value='l5' > تفصیلی1" + "<br>" +  
						"<input type='radio' name='level' id='level-l6' value='l6' >تفصیلی 2" + "<br>" +  
						"<input type='radio' name='level' id='level-l7' value='l7' >تفصیلی 3"
			},{
				xtype : "container",
				html :  "<input type='radio' name='level' id='level-l8' value='l8' > آیتم 1" + "<br>" +  
						"<input type='radio' name='level' id='level-l9' value='l9' >آیتم 2" + "<br>" +  
						"<input type='radio' name='level' id='level-l10' value='l10' >آیتم 3"
			},{
				xtype : "container",
				html : "ستون های تراز: "
			},{
				xtype : "container",
				html : 
					"<input type='radio' name='resultColumns' id='resultColumns-2' value='2' >دوستونی" + "<br>" +  
					"<input type='radio' name='resultColumns' id='resultColumns-4' value='4' checked>چهارستونی " + "<br>" +  
					"<input type='radio' name='resultColumns' id='resultColumns-6' value='6' >شش ستونی"
			},{
				xtype : "container",
				html : "<input type=checkbox checked name=IncludeRaw id=IncludeRaw> گزارش شامل اسناد خام نیز باشد." + "<br>" +
					"<input type=checkbox name=IncludeStart checked=true id=IncludeStart> گزارش شامل سند افتتاحیه باشد." + "<br>" + 	
					"<input type=checkbox name=IncludeEnd id=IncludeEnd> گزارش شامل سند اختتامیه باشد."+ "<br>" + 
					"<input type=checkbox name=RemainOnly id=RemainOnly checked> گزارش فقط حساب های مانده دار را لیست کند"
			}]
		},{
			xtype : "fieldset",
			colspan :4,
			width : 920,
			title : "رسم نمودار",
			items : [<?= $page_rpg->GetChartItems("AccReport_tarazObj","mainForm","taraz.php", 
					"AccReport_tarazObj.BeforeSubmit") ?>]
		}],
		buttons : [{
			text : "گزارش ساز",
			iconCls : "db",
			handler : function(){ReportGenerator.ShowReportDB(
						AccReport_tarazObj, 
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
					AccReport_tarazObj.get('excel').value = "true";
				}
			},
			iconCls : "excel"
		},{
			text : "پاک کردن گزارش",
			iconCls : "clear",
			handler : function(){
				AccReport_tarazObj.formPanel.getForm().reset();
				AccReport_tarazObj.get("mainForm").reset();
				AccReport_tarazObj.formPanel.down("[itemId=cmp_tafsiliID]").disable();
			}			
		}]
	});
}

AccReport_tarazObj = new AccReport_taraz();
</script>
<form id="mainForm">
	<center><br>
		<div id="main" ></div>
	</center>
	<input type="hidden" name="excel" id="excel">
</form>
