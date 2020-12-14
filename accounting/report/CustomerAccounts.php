<?php
//-----------------------------
//	Programmer	: SH.Jafarkhani
//	Date		: 95.10
//-----------------------------

require_once '../../header.inc.php';
require_once "ReportGenerator.class.php";
require_once '../docs/doc.data.php';

if(isset($_REQUEST["show"]))
	showSummary();
if(isset($_REQUEST["showflow"]))
	showFlow();


function showSummary(){
	$rpg = new ReportGenerator();
	$rpg->excel = !empty($_POST["excel"]);

	$param = array();
	$where = " AND (pasandaz.amount<>0 or kootah.amount<>0 or boland.amount<>0 or jari.amount<>0)";
	if(!empty($_POST["TafsiliID"]))
	{
		$where .= " AND t.TafsiliID=:t";
		$param[":t"] = $_POST["TafsiliID"];
	}
	$rpg->mysql_resource = GetAccountSummary(true, $where, $param);

	//echo PdoDataAccess::GetLatestQueryString();
	
	$rpg->addColumn("تفصیلی", "TafsiliDesc");
	if(isset($_POST["savingCost"]))	
	{
		$col = $rpg->addColumn("حساب مشتری", "pasandaz","ReportMoneyRender");
		$col->EnableSummary();
	}
	if(isset($_POST["shortCost"]))	
	{
		$col = 	$rpg->addColumn("کوتاه مدت", "kootah","ReportMoneyRender");
		$col->EnableSummary();
	}
	if(isset($_POST["longCost"]))	
	{
		$col = 	$rpg->addColumn("بلند مدت", "boland","ReportMoneyRender");
		$col->EnableSummary();
	}
	if(isset($_POST["currentCost"]))	
	{
		$col = 	$rpg->addColumn("جاری", "jari","ReportMoneyRender");
		$col->EnableSummary();
	}

	if(!$rpg->excel)
	{
		BeginReport();

		echo "<div style=display:none>" . PdoDataAccess::GetLatestQueryString() . "</div>";

		require_once "../../framework/baseInfo/baseInfo.class.php";
		$branchObj = new BSC_branches($_POST["BranchID"]);

		echo "<table style='border:2px groove #9BB1CD;border-collapse:collapse;width:100%'><tr>
				<td width=60px><img src='/framework/icons/logo.jpg' style='width:120px'></td>
				<td align='center' style='height:100px;vertical-align:middle;font-family:titr;font-size:15px'>
					گزارش خلاصه حساب مشتریان
					<br> ".
				$branchObj->BranchName . "<br>" . "دوره سال " .
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
	$rpg->generateReport();
	die();
}

function showFlow(){
	
	$where = "";
	$param = array(
		":c" => $_SESSION["accounting"]["CycleID"], 
		":b" => $_POST["BranchID"]);
	
	if(!empty($_POST["TafsiliID"]))
	{
		$where .= " AND di.TafsiliID=:t";
		$param[":t"] = $_POST["TafsiliID"];
	}
	$costs = array();
	if(isset($_POST["savingCost"]))		$costs[] = COSTID_saving;
	if(isset($_POST["shortCost"]))		$costs[] = COSTID_ShortDeposite;
	if(isset($_POST["longCost"]))		$costs[] = COSTID_LongDeposite;
	if(isset($_POST["currentCost"]))	$costs[] = COSTID_current;
	
	$query = "select 
			TafsiliDesc,
			LocalNo,DocDate,
			concat_ws(' - ',description,di.details) description,
			
			if(CostID=".COSTID_saving.",DebtorAmount,0) saving_debtor,
			if(CostID=".COSTID_saving.",CreditorAmount,0) saving_creditor,
			
			if(CostID=".COSTID_ShortDeposite.",DebtorAmount,0) Short_debtor,
			if(CostID=".COSTID_ShortDeposite.",CreditorAmount,0) Short_creditor,
			
			if(CostID=".COSTID_LongDeposite.",DebtorAmount,0) Long_debtor,
			if(CostID=".COSTID_LongDeposite.",CreditorAmount,0) Long_creditor,
			
			if(CostID=".COSTID_current.",DebtorAmount,0) current_debtor,
			if(CostID=".COSTID_current.",CreditorAmount,0) current_creditor
			
		from ACC_DocItems di
			join ACC_tafsilis using(TafsiliID)
			join ACC_docs d using(DocID)
		where d.CycleID=:c 
			AND d.BranchID=:b AND 
			di.CostID in(".implode(",",$costs).") 
			AND di.TafsiliType = " . TAFSILITYPE_PERSON . " $where			
		order by DocDate ";
	
	
	$temp = PdoDataAccess::runquery($query, $param);
	//print_r(ExceptionHandler::PopAllExceptions());
	
	$rpg = new ReportGenerator();
	$rpg->excel = !empty($_POST["excel"]);
	$rpg->mysql_resource = $temp;

	$rpg->addColumn("تفصیلی", "TafsiliDesc");
	$rpg->addColumn("سند", "LocalNo");
	$rpg->addColumn("تاریخ", "DocDate","ReportDateRender");
	$rpg->addColumn("شرح", "description");
	
	if(isset($_POST["savingCost"]))	
	{
		$col = $rpg->addColumn("بدهکار", "saving_debtor","ReportMoneyRender");
		$col->GroupHeader = "حساب مشتری";
		$col->EnableSummary();
		$col = $rpg->addColumn("بستانکار", "saving_creditor","ReportMoneyRender");
		$col->GroupHeader = "حساب مشتری";
		$col->EnableSummary();
		function savingRemainRender(&$row, $value, $BeforeAmount, $prevRow){

			if(!$prevRow)
				$row["saving_Sum"] = $row["saving_creditor"] - $row["saving_debtor"];
			else
				$row["saving_Sum"] = $prevRow["saving_Sum"] + $row["saving_creditor"] - $row["saving_debtor"];

			if(!empty($_POST["excel"]))
				return $row["saving_Sum"];
			else
				return "<div style=direction:ltr>" . number_format($row["saving_Sum"]) . "</div>";
		}
		$col = $rpg->addColumn("مانده حساب", "saving_debtor", "savingRemainRender", "");
		$col->GroupHeader = "حساب مشتری";
	}
	if(isset($_POST["shortCost"]))
	{
		$col = $rpg->addColumn("بدهکار", "Short_debtor","ReportMoneyRender");
		$col->GroupHeader = "حساب کوتاه مدت";
		$col->EnableSummary();
		$col = $rpg->addColumn("بستانکار", "Short_creditor","ReportMoneyRender");
		$col->GroupHeader = "حساب کوتاه مدت";
		$col->EnableSummary();
		function ShortRemainRender(&$row, $value, $BeforeAmount, $prevRow){

			if(!$prevRow)
				$row["Short_Sum"] = $row["Short_creditor"] - $row["Short_debtor"];
			else
				$row["Short_Sum"] = $prevRow["Short_Sum"] + $row["Short_creditor"] - $row["Short_debtor"];

			if(!empty($_POST["excel"]))
				return $row["Short_Sum"];
			else
				return "<div style=direction:ltr>" . number_format($row["Short_Sum"]) . "</div>";;
		}
		$col = $rpg->addColumn("مانده حساب", "saving_debtor", "ShortRemainRender");
		$col->GroupHeader = "حساب کوتاه مدت";
	}
	if(isset($_POST["longCost"]))	
	{
		$col = $rpg->addColumn("بدهکار", "Long_debtor","ReportMoneyRender");
		$col->GroupHeader = "حساب بلند مدت";
		$col->EnableSummary();
		$col = $rpg->addColumn("بستانکار", "Long_creditor","ReportMoneyRender");
		$col->GroupHeader = "حساب بلند مدت";
		$col->EnableSummary();
		function LongRemainRender(&$row, $value, $BeforeAmount, $prevRow){

			if(!$prevRow)
				$row["Long_Sum"] = $row["Long_creditor"] - $row["Long_debtor"];
			else
				$row["Long_Sum"] = $prevRow["Long_Sum"] + $row["Long_creditor"] - $row["Long_debtor"];

			if(!empty($_POST["excel"]))
				return $row["Long_Sum"];
			else
				return "<div style=direction:ltr>" . number_format($row["Long_Sum"]) . "</div>";;
		}
		$col = $rpg->addColumn("مانده حساب", "saving_debtor", "LongRemainRender");
		$col->GroupHeader = "حساب بلند مدت";
	}
	if(isset($_POST["currentCost"]))	
	{
		$col = $rpg->addColumn("بدهکار", "current_debtor","ReportMoneyRender");
		$col->GroupHeader = "حساب جاری";
		$col->EnableSummary();
		$col = $rpg->addColumn("بستانکار", "current_creditor","ReportMoneyRender");
		$col->GroupHeader = "حساب جاری";
		$col->EnableSummary();
		function currentRemainRender(&$row, $value, $BeforeAmount, $prevRow){

			if(!$prevRow)
				$row["current_Sum"] = $row["current_creditor"] - $row["current_debtor"];
			else
				$row["current_Sum"] = $prevRow["current_Sum"] + $row["current_creditor"] - $row["current_debtor"];

			if(!empty($_POST["excel"]))
				return $row["current_Sum"];
			else
				return "<div style=direction:ltr>" . number_format($row["current_Sum"]) . "</div>";;
		}
		$col = $rpg->addColumn("مانده حساب", "saving_debtor", "currentRemainRender");
		$col->GroupHeader = "حساب جاری";
	}
	if(!$rpg->excel)
	{
		BeginReport();

		echo "<div style=display:none>" . PdoDataAccess::GetLatestQueryString() . "</div>";

		require_once "../../framework/baseInfo/baseInfo.class.php";
		$branchObj = new BSC_branches($_POST["BranchID"]);

		echo "<table style='border:2px groove #9BB1CD;border-collapse:collapse;width:100%'><tr>
				<td width=60px><img src='/framework/icons/logo.jpg' style='width:120px'></td>
				<td align='center' style='height:100px;vertical-align:middle;font-family:titr;font-size:15px'>
					گزارش خلاصه حساب مشتریان
					<br> ".
				$branchObj->BranchName . "<br>" . "دوره سال " .
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
	$rpg->generateReport();
	die();
	
}
?>
<script>
	
AccReport_CustomerAccount.prototype = {
	
	TabID : '<?= $_REQUEST["ExtTabID"]?>',
	address_prefix : "<?= $js_prefix_address?>",

	get : function(elementID){
		return findChild(this.TabID, elementID);
	}
}

AccReport_CustomerAccount.prototype.showReport = function(btn, e)
{
	this.form = this.get("mainForm")
	this.form.target = "_blank";
	this.form.method = "POST";
	this.form.action =  this.address_prefix + "CustomerAccounts.php?show=true";
	this.form.submit();
	this.get("excel").value = "";
	return;
}

AccReport_CustomerAccount.prototype.showReport2 = function(btn, e)
{
	this.form = this.get("mainForm")
	this.form.target = "_blank";
	this.form.method = "POST";
	this.form.action =  this.address_prefix + "CustomerAccounts.php?showflow=true";
	this.form.submit();
	this.get("excel").value = "";
	return;
}

function AccReport_CustomerAccount()
{	
	this.formPanel = new Ext.form.Panel({
		renderTo : this.get("main"),
		frame : true,
		layout :{
			type : "table",
			columns :1
		},
		bodyStyle : "text-align:right;padding:5px",
		title : "گزارش خلاصه حساب مشتریان",
		defaults : {
			labelWidth :100,
			width : 270
		},
		width : 600,
		items :[{
			xtype : "combo",
			width : 400,
			displayField : "TafsiliDesc",
			fieldLabel : "تفصیلی",
			valueField : "TafsiliID",
			itemId : "cmp_tafsiliID",
			hiddenName : "TafsiliID",
			store : new Ext.data.Store({
				fields:["TafsiliID","TafsiliDesc"],
				proxy: {
					type: 'jsonp',
					url: this.address_prefix + '../baseinfo/baseinfo.data.php?task=GetAllTafsilis&'+
						'TafsiliType=<?= TAFSILITYPE_PERSON ?>',
					reader: {root: 'rows',totalProperty: 'totalCount'}
				}
			})
		},{
			xtype : "fieldset",
			width : 500,
			title : "حساب ها",
			items : [{
				xtype: "container",
				html : "<input type=checkbox name=savingCost> حساب مشتری &nbsp;&nbsp;&nbsp;" +
					"<input type=checkbox name=shortCost> حساب کوتاه مدت &nbsp;&nbsp;&nbsp;" +
					"<input type=checkbox name=longCost> حساب بلند مدت &nbsp;&nbsp;&nbsp;" +
					"<input type=checkbox name=currentCost> حساب جاری &nbsp;&nbsp;&nbsp;" 
			}]
		},{
			xtype : "container",
			html : "<input type=checkbox checked name=IncludeRaw> گزارش شامل اسناد پیش نویس نیز باشد"
		}],
		buttons : [{
			text : "گزارش خلاصه",
			handler : Ext.bind(this.showReport,this),
			iconCls : "report"
		},{
			text : "excel خلاصه",
			handler : Ext.bind(this.showReport,this),
			listeners : {
				click : function(){
					AccReport_CustomerAccountObj.get('excel').value = "true";
				}
			},
			iconCls : "excel"
		},{
			text : "گزارش گردش",
			handler : Ext.bind(this.showReport2,this),
			iconCls : "report"
		},{
			text : "excel گردش",
			handler : Ext.bind(this.showReport2,this),
			listeners : {
				click : function(){
					AccReport_CustomerAccountObj.get('excel').value = "true";
				}
			},
			iconCls : "excel"
		},{
			text : "پاک کردن گزارش",
			iconCls : "clear",
			handler : function(){
				AccReport_CustomerAccountObj.formPanel.getForm().reset();
				AccReport_CustomerAccountObj.get("mainForm").reset();
			}			
		}]
	});
}

AccReport_CustomerAccountObj = new AccReport_CustomerAccount();
</script>
<form id="mainForm">
	<center><br>
		<div id="main" ></div>
	</center>
	<input type="hidden" name="excel" id="excel">
</form>
