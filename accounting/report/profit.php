<?php
//-----------------------------
//	Programmer	: SH.Jafarkhani
//	Date		: 94.06
//-----------------------------

require_once '../../header.inc.php';
require_once "ReportGenerator.class.php";

function MakeData(){
		
		$StartDate = empty($_POST["StartDate"]) ? "" : DateModules::shamsi_to_miladi($_POST["StartDate"]);
		$EndDate = empty($_POST["EndDate"]) ? "" : DateModules::shamsi_to_miladi($_POST["EndDate"]);
		$ProfitPercent = $_POST["ProfitPercent"];
		//------------ get sum  ----------------
		$where = "";
		$params = array();
		if(!empty($_POST["CycleID"]))
		{
			$where .= " AND CycleID=:cycle";
			$params[":cycle"] = $_POST["CycleID"];
		}
		if(!empty($_POST["BranchID"]))
		{
			$where .= " AND BranchID=:bid";
			$params[":bid"] = $_POST["BranchID"];
		}
		if(!empty($_POST["TafsiliType"]))
		{
			$where .= " AND di.TafsiliType=:tt";
			$params[":tt"] = $_POST["TafsiliType"];
		}
		if(!empty($_POST["TafsiliID"]))
		{
			$where .= " AND di.TafsiliID=:tid";
			$params[":tid"] = $_POST["TafsiliID"];
		}
		if(!empty($_POST["TafsiliType2"]))
		{
			$where .= " AND di.TafsiliType2=:tt2";
			$params[":tt2"] = $_POST["TafsiliType2"];
		}
		if(!empty($_POST["TafsiliID2"]))
		{
			$where .= " AND di.TafsiliID2=:tid2";
			$params[":tid2"] = $_POST["TafsiliID2"];
		}
		if(!empty($_POST["CostID"]))
		{
			InputValidation::validate($_POST["CostID"], InputValidation::Pattern_NumComma);
			$where .= " AND CostID in (".$_POST["CostID"].")";
		}
		//------------ get the remainder amount -------------
		$TraceArr = array();
		if($StartDate != "")
		{
			$params2 = $params;
			$params2[":startDate"] = $StartDate;
			$dt = PdoDataAccess::runquery("
				select 
					group_concat( distinct
						concat_ws(' - ' , b1.BlockCode,b2.BlockCode,b3.BlockCode,b4.BlockCode,
							b1.BlockDesc,b2.BlockDesc,b3.BlockDesc,b4.BlockDesc)
					SEPARATOR '<br>') CostDescs,
					sum(CreditorAmount-DebtorAmount) amount
					
				from ACC_DocItems di join ACC_docs using(DocID)
				join ACC_CostCodes cc using(CostID)
				join ACC_blocks b1 on(level1=b1.BlockID)
				left join ACC_blocks b2 on(level2=b2.BlockID)
				left join ACC_blocks b3 on(level3=b3.BlockID)
				left join ACC_blocks b4 on(level4=b4.BlockID)
				where DocDate <= :startDate " . $where . " 
				", $params2);
			
			if(count($dt) > 0)
			{
				$row = $dt[0];
				$row["DocDate"] = $StartDate;
				$row["DocDesc"] = "مانده قبل";
				$TraceArr[] = array(
						"row" => $row,
						"profit" => 0,
						"ReturnProfit" => 0,
						"days" => 0
				);		
			}
		}
		//------------ get the Deposite amount -------------
		$params2 = $params;
		if($StartDate != "")
		{
			$where .= " AND DocDate > :startDate";
			$params2[":startDate"] = $StartDate;
		}
		if($EndDate != "")
		{
			$where .= " AND DocDate <= :endDate";
			$params2[":endDate"] = $EndDate;
		}
		
		$dt = PdoDataAccess::runquery("
			select DocDate,group_concat(details SEPARATOR '<br>') DocDesc,
					group_concat( distinct
						concat_ws(' - ' , b1.BlockCode,b2.BlockCode,b3.BlockCode,b4.BlockCode,
							b1.BlockDesc,b2.BlockDesc,b3.BlockDesc,b4.BlockDesc)
					SEPARATOR '<br>') CostDescs,
					sum(CreditorAmount-DebtorAmount) amount
			from ACC_DocItems di join ACC_docs using(DocID)
			join ACC_CostCodes cc using(CostID)
			join ACC_blocks b1 on(level1=b1.BlockID)
			left join ACC_blocks b2 on(level2=b2.BlockID)
			left join ACC_blocks b3 on(level3=b3.BlockID)
			left join ACC_blocks b4 on(level4=b4.BlockID)
			where 1=1 " . $where . "
				
			group by DocDate
			order by DocDate", $params2);
		
		if($_SESSION["USER"]["UserName"] == "admin")
		{
			echo PdoDataAccess::GetLatestQueryString();
		}
		
		$lastDate = $StartDate != "" ? $StartDate : $dt[0]["DocDate"];
		$prevDays = 0;
		for($i=0; $i<count($dt)+1; $i++)
		{
			if($i<count($dt))
			{
				$row = $dt[$i];			
				$EndDate = $row["DocDate"];
			}
			else
			{
				$row = array("amount" => 0);
				$EndDate = DateModules::Now();
			}
			
			$days = DateModules::GDateMinusGDate($EndDate, $lastDate);
			if($row["amount"]*1 < 0)
			{
				$days--;
				$days += $prevDays;
				$prevDays = 1;
			}
			else
			{
				$days += $prevDays;
				$prevDays = 0;
			}
			
			//-------------- compute profits ----------------
			if(count($TraceArr) != 0)
			{
				$amount = $TraceArr[count($TraceArr)-1]["row"]["amount"];
				$profit = $amount * $days * $ProfitPercent/36500;
				$TraceArr[count($TraceArr)-1]["days"] = $days;
				$TraceArr[count($TraceArr)-1]["profit"] = $profit;
			}
			
			if($i < count($dt))
				$TraceArr[] = array(
					"row" => $row,
					"days" => 0,
					"profit" => 0
				);	
			if($i<count($dt))
				$lastDate = $dt[$i]["DocDate"];
		}
		
		echo '<META http-equiv=Content-Type content="text/html; charset=UTF-8" ><body dir="rtl">';
		echo '<link rel="stylesheet" type="text/css" href="/generalUI/fonts/fonts.css" />';
		echo "<style>
				table { border-collapse:collapse; width:100%}
				#header {background-color : blue; color : white; font-weight:bold}
				#footer {background-color : #bbb;}
				td {font-family : nazanin; font-size:16px; padding:4px}
			</style>";
		echo "<table></table>";
		echo "<table border=1>
			<tr id=header>
				<td>تاریخ</td>
				<td>کدهای حساب</td>
				<td>شرح</td>
				<td>مبلغ گردش</td>
				<td>مانده حساب</td>
				<td>تعداد روز</td>
				<td>درصد سود</td>
				<td>سود</td>
			</tr>";
		$amount = 0;
		$sumProfit = 0;
		for($i=0; $i<count($TraceArr); $i++)
		{
			$row = $TraceArr[$i];

			$amount += $row["row"]["amount"]*1;
			$sumProfit += $row["profit"]*1;
			echo "<tr>
					<td>" . DateModules::miladi_to_shamsi($row["row"]["DocDate"]) . "</td>
					<td>" . $row["row"]["CostDescs"] . "</td>
					<td>" . $row["row"]["DocDesc"] . "</td>
					<td>" . number_format($row["row"]["amount"]) . "</td>
					<td>" . number_format($amount) . "</td>
					<td>" . $row["days"] . "</td>
					<td>" . $ProfitPercent . "</td>				
					<td>" . number_format($row["profit"]) . "</td>
				</tr>";
		}
		echo "<tr id=footer>
				<td colspan=6>جمع</td>
				<td>" . number_format($sumProfit) . "</td>
			</tr>";
		echo "</table>";
	}
	
if(isset($_REQUEST["show"]))
{
	MakeData();
}
?>
<script>
AccReport_profit.prototype = {
	TabID : '<?= $_REQUEST["ExtTabID"]?>',
	address_prefix : "<?= $js_prefix_address?>",

	get : function(elementID){
		return findChild(this.TabID, elementID);
	}
}

AccReport_profit.prototype.showReport = function(btn, e)
{
	if(!this.formPanel.getForm().isValid())
		return;
	this.form = this.get("mainForm")
	this.form.target = "_blank";
	this.form.method = "POST";
	this.form.action =  this.address_prefix + "profit.php?show=true";
	this.form.submit();
	this.get("excel").value = "";
	return;
}

function AccReport_profit()
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
		title : "گزارش سود",
		defaults : {
			labelWidth :100,
			width : 270
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
			xtype : "checkcombo",
			width : 540,
			fieldLabel : "کد حساب",
			colspan : 2,
			store: new Ext.data.Store({
				fields:["CostID","CostCode","CostDesc", "TafsiliType1","TafsiliType2",{
					name : "fullDesc",
					convert : function(value,record){
						return "[ " + record.data.CostCode + " ] " + record.data.CostDesc
					}				
				}],
				proxy: {
					type: 'jsonp',
					url: this.address_prefix + '../baseinfo/baseinfo.data.php?task=SelectCostCode',
					reader: {root: 'rows',totalProperty: 'totalCount'}
				},
				pageSize : 1000,
				autoLoad : true
			}),
			queryMode : "local",
			hiddenName : "CostID",
			valueField : "CostID",
			displayField : "fullDesc",
			listConfig: {
				loadingText: 'در حال جستجو...',
				emptyText: 'فاقد اطلاعات'
			}
		},{
			xtype : "combo",
			displayField : "InfoDesc",
			fieldLabel : "گروه تفصیلی",
			valueField : "InfoID",
			hiddenName : "TafsiliType",
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
					el = AccReport_profitObj.formPanel.down("[itemId=cmp_tafsiliID]");
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
			hiddenName : "TafsiliType2",
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
					el = AccReport_profitObj.formPanel.down("[itemId=cmp_tafsiliID2]");
					el.setValue();
					el.enable();
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
			xtype : "shdatefield",
			name : "StartDate",
			fieldLabel : "از تاریخ"
		},{
			xtype : "shdatefield",
			name : "EndDate",
			fieldLabel : "تا تاریخ"
		},{
			xtype : "numberfield",
			name : "ProfitPercent",
			fieldLabel : "درصد سود",
			hideTrigger : true
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
					AccReport_profitObj.get('excel').value = "true";
				}
			},
			iconCls : "excel"
		},{
			text : "پاک کردن گزارش",
			iconCls : "clear",
			handler : function(){
				AccReport_profitObj.formPanel.getForm().reset();
				AccReport_profitObj.get("mainForm").reset();
			}			
		}]
	});
}

AccReport_profitObj = new AccReport_profit();
</script>
<form id="mainForm">
	<center><br>
		<div id="main" ></div>
	</center>
	<input type="hidden" name="excel" id="excel">
</form>
