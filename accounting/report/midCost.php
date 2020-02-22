<?php
//-----------------------------
//	Programmer	: SH.Jafarkhani
//	Date		: 94.06
//-----------------------------

require_once '../../header.inc.php';
require_once "ReportGenerator.class.php";

if(isset($_REQUEST["show"]))
{
	function MakeData(){
		
		$CostID = $_POST["CostID"];
		if(empty($_POST["StartDate"]))
		{
			$FirstDayOfYear = empty($_POST["CycleID"]) ? "" : 
					DateModules::shamsi_to_miladi($_POST["CycleID"] . "01/01", "-");
			$StartDate = empty($_POST["CycleID"]) ? "" : 
				( isset($_REQUEST["IncludeStart"]) ? "" : $FirstDayOfYear );
		}
		else
			$StartDate = DateModules::shamsi_to_miladi($_POST["StartDate"], "-");
		
		$EndDate = empty($_POST["EndDate"]) ? DateModules::Now() : 
						DateModules::shamsi_to_miladi($_POST["EndDate"], "-");
		//------------ get sum  ----------------
		$where = $where2 = "";
		$params = array(":cid" => $CostID);
		
		$query = "
			select DocDate,CostCode,TafsiliDesc,sum(CreditorAmount-DebtorAmount) amount
			from ACC_DocItems di
				join ACC_docs d using(DocID)
				join ACC_tafsilis t using(TafsiliType,TafsiliID)
				join ACC_CostCodes cc using(CostID)

			where di.CostID = :cid";
		if(!empty($_POST["CycleID"]))
		{
			$where .= " AND CycleID = :cycle";
			$where2 .= " AND CycleID = :cycle";
			$params[":cycle"] = $_POST["CycleID"];
		}
		if(!empty($_POST["BranchID"]))
		{
			$where .= " AND BranchID= :bid";
			$where2 .= " AND BranchID= :bid";
			$params[":bid"] = $_POST["BranchID"];
		}
		if(!empty($StartDate))
		{
			$where .= " AND DocDate >= :sdate";
			$where2 .= " AND DocDate < :sdate";
			$params[":sdate"] = $StartDate;
		}
		if(!empty($EndDate))
		{
			$where .= " AND DocDate <= :edate";
			$params[":edate"] = $EndDate;
		}
		if(!empty($_POST["TafsiliID"]))
		{
			$where .= " AND di.TafsiliID = :tid";
			$where2 .= " AND di.TafsiliID = :tid";
			$params[":tid"] = $_POST["TafsiliID"];
		}
		if(!empty($_POST["TafsiliID2"]))
		{
			$where .= " AND di.TafsiliID2 = :tid2";
			$where2 .= " AND di.TafsiliID2 = :tid2";
			$params[":tid2"] = $_POST["TafsiliID2"];
		}
		if(!isset($_REQUEST["IncludeRaw"]))
		{
			$where .= " AND d.StatusID != " . ACC_STEPID_RAW;
			$where2 .= " AND d.StatusID != " . ACC_STEPID_RAW;
		}
		/*if(!isset($_REQUEST["IncludeStart"]))
		{
			$where .= " AND d.DocType != " . DOCTYPE_STARTCYCLE;
			$where2 .= " AND d.DocType != " . DOCTYPE_STARTCYCLE;
		}
		if(!isset($_REQUEST["IncludeEnd"]))
		{
			$where .= " AND d.DocType != " . DOCTYPE_ENDCYCLE;
			$where2 .= " AND d.DocType != " . DOCTYPE_ENDCYCLE;
		}
		*/
		$query .= $where . " group by DocDate order by DocDate";
		
		$dt = PdoDataAccess::runquery($query, $params);
		//echo PdoDataAccess::GetLatestQueryString();
		
		if(count($dt) == 0)
		{
			$msg = "تفصیلی مورد نظر فاقد سندی با حساب فوق می باشد";
			echo $msg;
			die();
		}
		//------------ get the remainder amount -------------
		$remainder = 0;
		if($StartDate != "")
		{
			$params2 = $params;
			unset($params2[":edate"]);
			$dt2 = PdoDataAccess::runquery("
				select DocDate,CostCode,TafsiliDesc,sum(CreditorAmount-DebtorAmount) amount
				from ACC_DocItems di
					join ACC_docs d using(DocID)
					join ACC_tafsilis t using(TafsiliType,TafsiliID)
					join ACC_CostCodes cc using(CostID)

				where di.CostID = :cid " . $where2 , $params2);
			if(count($dt2) > 0)
			{
				$remainder = $dt2[0]["amount"];
			}
		}	
		if($_SESSION["USER"]["UserName"] == "admin")
		{
			echo PdoDataAccess::GetLatestQueryString();
			echo "<bR>" . $remainder;
		}
		//------------ get the Deposite amount -------------
		$TraceArr = array();
		$remain = $dt[0]["amount"]*1  + $remainder;
		$totalDays = 0;
		$totalAmount = 0;
		$TraceArr[] = array(
			"TafsiliDesc" => $dt[0]["TafsiliDesc"],
			"CostCode" => $dt[0]["CostCode"],
			"Date" => $dt[0]["DocDate"],
			"amount" => $dt[0]["amount"]*1,
			"remain" => $dt[0]["amount"]*1 + $remainder,
			"days" => 0,
			"average" => 0
		);
		for($i=1; $i < count($dt); $i++)
		{
			$days = DateModules::GDateMinusGDate($dt[$i]["DocDate"],$dt[$i-1]["DocDate"]);
			$totalDays += $days;

			$totalAmount += $remain*$days;
			$remain += $dt[$i]["amount"];

			$TraceArr[count($TraceArr)-1]["days"] = $days;
			$TraceArr[count($TraceArr)-1]["average"] = $totalAmount / $totalDays;
			$TraceArr[] = array(
				"Date" => $dt[$i]["DocDate"],
				"amount" => $dt[$i]["amount"],
				"remain" => $remain,
				"days" => 0
			);
		}
		$days = DateModules::GDateMinusGDate($EndDate,$dt[$i-1]["DocDate"]);
		$totalAmount += $remain*$days;
		$totalDays += $days;
		$totalAmount = round($totalAmount / $totalDays);
		$TraceArr[count($TraceArr)-1]["days"] = $days;
		$TraceArr[count($TraceArr)-1]["average"] = $totalAmount;

		return $TraceArr;
	}
	
	$rpg = new ReportGenerator();
	$rpg->mysql_resource = MakeData();
	$rpg->excel = !empty($_POST["excel"]);

	function InRender($row){
		if($row["amount"]*1 < 0)
			return 0;
		return number_format($row["amount"]);
	}

	function OutRender($row){
		if($row["amount"]*1 > 0)
			return 0;
		return number_format($row["amount"]);
	}

	$rpg->addColumn("تاریخ", "Date", "ReportDateRender");
	$col = $rpg->addColumn("واریز", "amount", "InRender");
	$col = $rpg->addColumn("برداشت", "amount", "OutRender");
	$rpg->addColumn("مانده", "remain", "ReportMoneyRender");
	$col = $rpg->addColumn("تعداد روز", "days");
	$rpg->addColumn("میانگین", "average", "ReportMoneyRender");

	if(!$rpg->excel)
	{
		BeginReport();
		echo
		"<table style='border:2px groove #9BB1CD;border-collapse:collapse;width:100%'><tr>
				<td width=60px><img src='/framework/icons/logo.jpg' style='width:120px'></td>
				<td align='center' style='height:100px;vertical-align:middle;font-family:titr;font-size:15px'>
					گزارش میانگین حساب
					 <br> کد حساب : [ " . $rpg->mysql_resource[0]["CostCode"] . " ]" .
					"<br>تفصیلی :  " . $rpg->mysql_resource[0]["TafsiliDesc"] .
				"</td>
				<td width='200px' align='center' style='font-family:tahoma;font-size:11px'>تاریخ تهیه گزارش : " 
			. DateModules::shNow() . "<br>";
		if(!empty($_POST["StartDate"]))
		{
			echo "<br>گزارش از تاریخ : " . $_POST["StartDate"];
		}
		if(!empty($_POST["EndDate"]))
		{
			echo "<br>گزارش تا تاریخ : " . $_POST["EndDate"];
		}
		echo "</td></tr></table>";
	}
	
	$rpg->generateReport();
	die();
}
?>
<script>
AccReport_mid.prototype = {
	TabID : '<?= $_REQUEST["ExtTabID"]?>',
	address_prefix : "<?= $js_prefix_address?>",

	get : function(elementID){
		return findChild(this.TabID, elementID);
	}
}

AccReport_mid.prototype.showReport = function(btn, e)
{
	if(!this.formPanel.getForm().isValid())
		return;
	this.form = this.get("mainForm")
	this.form.target = "_blank";
	this.form.method = "POST";
	this.form.action =  this.address_prefix + "midCost.php?show=true";
	this.form.submit();
	this.get("excel").value = "";
	return;
}

function AccReport_mid()
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
		title : "گزارش میانگین حساب",
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
			xtype : "combo",
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
				}
			}),
			typeAhead: false,
			allowBlank : false,
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
					el = AccReport_midObj.formPanel.down("[itemId=cmp_tafsiliID]");
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
			allowBlank : false,
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
					el = AccReport_midObj.formPanel.down("[itemId=cmp_tafsiliID2]");
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
			allowBlank : false,
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
			xtype : "container",
			colspan : 2,
			html : "<input type=checkbox checked name=IncludeRaw> گزارش شامل اسناد پیش نویس نیز باشد"
		}/*,{
			xtype : "container",
			colspan : 2,
			html : "<input type=checkbox name=IncludeStart> گزارش شامل اسناد افتتاحیه باشد"
		},{
			xtype : "container",
			colspan : 2,
			html : "<input type=checkbox name=IncludeEnd> گزارش شامل اسناد اختتامیه باشد"
		}*/],
		buttons : [{
			text : "گزارش ساز",
			iconCls : "db",
			handler : function(){ReportGenerator.ShowReportDB(
						AccReport_midObj, 
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
					AccReport_midObj.get('excel').value = "true";
				}
			},
			iconCls : "excel"
		},{
			text : "پاک کردن گزارش",
			iconCls : "clear",
			handler : function(){
				AccReport_midObj.formPanel.getForm().reset();
				AccReport_midObj.get("mainForm").reset();
			}			
		}]
	});
}

AccReport_midObj = new AccReport_mid();
</script>
<form id="mainForm">
	<center><br>
		<div id="main" ></div>
	</center>
	<input type="hidden" name="excel" id="excel">
</form>
