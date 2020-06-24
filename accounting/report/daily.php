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
			cc.CostCode,
			b1.BlockCode level1_code,
			b1.BlockDesc level1_desc,
			concat_ws('-',b1.BlockCode,ifnull(b2.BlockCode,'00')) level2_code,
			concat_ws('-',b1.BlockDesc,b2.BlockDesc) level2_desc,
			concat_ws('-',b1.BlockCode,ifnull(b2.BlockCode,'00'),ifnull(b3.BlockCode,'00')) level3_code,
			concat_ws('-',b1.BlockDesc,b2.BlockDesc,b3.BlockDesc) level3_desc,
			concat_ws('-',b1.BlockCode,ifnull(b2.BlockCode,'00'),ifnull(b3.BlockCode,'00'),ifnull(b4.BlockCode,'00')) level4_code,
			concat_ws('-',b1.BlockDesc,b2.BlockDesc,b3.BlockDesc,b4.BlockDesc) level4_desc,
			concat_ws('-',b1.BlockCode,ifnull(b2.BlockCode,'00'),ifnull(b3.BlockCode,'00'),ifnull(b4.BlockCode,'00'),t.TafsiliID) level5_code,
			concat_ws('-',b1.BlockDesc,b2.BlockDesc,b3.BlockDesc,b4.BlockDesc,t.TafsiliDesc) level5_desc,
			concat_ws('-',b1.BlockCode,ifnull(b2.BlockCode,'00'),ifnull(b3.BlockCode,'00'),ifnull(b4.BlockCode,'00'),t.TafsiliID,t2.TafsiliID) level6_code,
			concat_ws('-',b1.BlockDesc,b2.BlockDesc,b3.BlockDesc,b4.BlockDesc,t.TafsiliDesc,t2.TafsiliDesc) level6_desc,
			DocType,
			DocDate,
			if(DocType in(".DOCTYPE_ENDCYCLE.",".DOCTYPE_STARTCYCLE."), DocDate, substr(jdate,6,2)  ) DocDate2,
			if(d.DocType=".DOCTYPE_ENDCYCLE.",1,0) IsEndDoc,
			di.details,
			sum(DebtorAmount) DSUM, 
			sum(CreditorAmount) CSUM
			
		from ACC_DocItems di
			join ACC_docs d using(docID)
			left join dates on(DocDate=GDate)
			join ACC_CostCodes cc using(CostID)
			left join ACC_blocks b1 on(cc.level1=b1.BlockID)
			left join ACC_blocks b2 on(cc.level2=b2.BlockID)
			left join ACC_blocks b3 on(cc.level3=b3.BlockID)
			left join ACC_blocks b4 on(cc.level4=b4.BlockID)
			left join ACC_tafsilis t on(t.TafsiliID=di.TafsiliID)
			left join ACC_tafsilis t2 on(t2.TafsiliID=di.TafsiliID2)
			
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
	 
	if(!isset($_REQUEST["IncludeRaw"]))
		$query .= " AND d.StatusID != " . ACC_STEPID_RAW;
	
	$group = $_POST['ReportLevel'];
	if($_POST["ReportDate"] == "month")
		$groupDate = "if(DocType in(".DOCTYPE_ENDCYCLE.",".DOCTYPE_STARTCYCLE."), DocDate, substr(jdate,6,2)  )";
	else
		$groupDate = "DocDate";
	
	$query .= " group by if ( DocType not in(".DOCTYPE_ENDCYCLE.",".DOCTYPE_STARTCYCLE.") , 2, DocType),
						 $groupDate,
						 ".$group."_code,if(DebtorAmount>0,0,1) ";
	$query .= " order by if ( DocType not in(".DOCTYPE_ENDCYCLE.",".DOCTYPE_STARTCYCLE.") , 2, DocType),
						$groupDate,
						if(DebtorAmount>0,0,1),".$group."_code,DSUM,CSUM";
	
	$dataTable = PdoDataAccess::runquery($query, $whereParam);
	
	if($_SESSION["USER"]["UserName"] == "admin")
	{
		print_r(ExceptionHandler::PopAllExceptions());
		echo PdoDataAccess::GetLatestQueryString();
	}
	
	$curDate = '';
	$index = 0;
	$curIsEndDoc = '';
	for($i=0; $i < count($dataTable); $i++)
	{
		$row = & $dataTable[$i];
		
		if($row["DocType"] == DOCTYPE_STARTCYCLE || $row["DocType"] == DOCTYPE_ENDCYCLE)
			$DocDate2 = DateModules::miladi_to_shamsi ($row["DocDate"]);
		else
		{
			if($_POST["ReportDate"] == "month")
			{
				$DocDate = DateModules::miladi_to_shamsi($row["DocDate"]);
				$year = DateModules::GetYear($DocDate);
				$DocDate2 = $year . "/" . $row["DocDate2"] . "/" . DateModules::DaysOfMonth($year,$row["DocDate2"]*1);
			}
			else
				$DocDate2 = DateModules::miladi_to_shamsi($row["DocDate"]);
		}
		
		if($curDate != $DocDate2)
		{
			$index++;
			$curDate = $DocDate2;
		}		
		
		$row["DocNo"] = $index;
		$row["NewDocDate"] = $DocDate2;
	}
	
	$rpg = new ReportGenerator();
	$rpg->rowNumber = true;
	$rpg->page_size = $_POST["PageRecord"]*1 ;
	$rpg->paging = true;
	$rpg->excel = !empty($_POST["excel"]);
	$rpg->mysql_resource = $dataTable;
	if(!$rpg->excel)
	{
		BeginReport();
		echo "<table style='border:2px groove #9BB1CD;border-collapse:collapse;width:100%'><tr>
				<td width=60px><img src='/framework/icons/logo.jpg' style='width:120px'></td>
				<td align='center' style='height:100px;vertical-align:middle;font-family:titr;font-size:15px'>
					گزارش دفتر روزنامه
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
	
	$rpg->addColumn("شماره سریال", "DocNo"); 
	$rpg->addColumn("تاریخ سند", "NewDocDate");
	
	$rpg->addColumn("کد حساب", $group . "_code"); 
	$rpg->addColumn("شرح حساب", $group . "_desc");
	
	$col = $rpg->addColumn("بدهکار", "DSUM", "ReportMoneyRender");
	$col->EnableSummary();
	$col = $rpg->addColumn("بستانکار", "CSUM", "ReportMoneyRender");
	$col->EnableSummary();

	echo $rpg->generateReport();
	die();
}
?>
<script>
AccReport_daily.prototype = {
	TabID : '<?= $_REQUEST["ExtTabID"]?>',
	address_prefix : "<?= $js_prefix_address?>",

	get : function(elementID){
		return findChild(this.TabID, elementID);
	}
}

AccReport_daily.prototype.showReport = function(btn, e)
{
	this.form = this.get("mainForm")
	this.form.target = "_blank";
	this.form.method = "POST";
	this.form.action =  this.address_prefix + "daily.php?show=true";
	this.form.submit();
	this.get("excel").value = "";
	return;
}

function AccReport_daily()
{
	this.formPanel = new Ext.form.Panel({
		renderTo : this.get("main"),
		frame : true,
		layout :{
			type : "table",
			columns :2
		},
		bodyStyle : "text-align:right;padding:5px",
		title : "گزارش  دفتر روزنامه",
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
			xtype : "fieldset",
			title : "گزارش بر اساس",
			colspan : 2,
			html : "<input type=radio checked name=ReportLevel value='level1'>کل" + "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;" +
					"<input type=radio name=ReportLevel value='level2'>معین1" + "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;" +
					"<input type=radio name=ReportLevel value='level3'>معین2" + "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;" +
					"<input type=radio name=ReportLevel value='level4'>معین3" + "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;" +
					"<input type=radio name=ReportLevel value='level5'>تفصیلی" + "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;" +
					"<input type=radio name=ReportLevel value='level6'>تفصیلی2" + "<br><br>" + 
					
					"<input type=radio checked name=ReportDate value='month'>ماهانه" + "&nbsp;&nbsp;&nbsp; " + 
					"<input type=radio name=ReportDate value='day'>روزانه" + "&nbsp;&nbsp;&nbsp; "
					
		},{
			xtype : "shdatefield",
			name : "fromDate",
			fieldLabel : "تاریخ سند از"
		},{
			xtype : "shdatefield",
			name : "toDate",
			fieldLabel : "تا"
		},{
			xtype : "textfield",
			value : 26,
			colspan : 2,
			fieldLabel : "تعداد رکورد در صفحه",
			name : "PageRecord"
		},{
			xtype : "container",
			colspan : 2,
			html : "&nbsp;<input type=checkbox checked name=IncludeRaw> گزارش شامل اسناد پیش نویس نیز باشد"
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
					AccReport_dailyObj.get('excel').value = "true";
				}
			},
			iconCls : "excel"
		},{
			text : "پاک کردن گزارش",
			iconCls : "clear",
			handler : function(){
				AccReport_dailyObj.formPanel.getForm().reset();
				AccReport_dailyObj.get("mainForm").reset();
			}			
		}]
	});
}

AccReport_dailyObj = new AccReport_daily();
</script>
<form id="mainForm">
	<center><br>
		<div id="main" ></div>
	</center>
	<input type="hidden" name="excel" id="excel">
</form>