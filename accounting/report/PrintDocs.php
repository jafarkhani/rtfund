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
	$query = "select concat_ws(' - ',b1.BlockDesc, b2.BlockDesc, b3.BlockDesc, b4.BlockDesc) CostDesc,
			cc.CostCode,
			d.DocDate,
			if(d.DocType=".DOCTYPE_ENDCYCLE.",1,0) IsEndDoc,
			b.InfoDesc TafsiliType,
			t.TafsiliDesc,
			t2.TafsiliDesc TafsiliDesc2,
			di.details,
			sum(DebtorAmount) DSUM, 
			sum(CreditorAmount) CSUM
			
		from ACC_DocItems di
			join ACC_docs d using(docID)
			join ACC_CostCodes cc using(CostID)
			left join ACC_blocks b1 on(cc.level1=b1.BlockID)
			left join ACC_blocks b2 on(cc.level2=b2.BlockID)
			left join ACC_blocks b3 on(cc.level3=b3.BlockID)
			left join ACC_blocks b4 on(cc.level4=b4.BlockID)
			left join BaseInfo b on(di.TafsiliType=InfoID AND TypeID=2)
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
	
	if(!isset($_REQUEST["IncludeRaw"]))
		$query .= " AND d.StatusID != " . ACC_STEPID_RAW;
	
	//------------------- group statement -----------------
	$query .= " group by if(DocType=".DOCTYPE_ENDCYCLE.",2,1),DocDate,cc.CostCode";
	if(isset($_REQUEST["TafsiliGroup"]) && isset($_REQUEST["Tafsili2Group"]))
	{
		$query .= ",ItemID";
		$colspan = 5;
	}
	else{
		
		if(isset($_REQUEST["TafsiliGroup"]))
		{
			$query .= ",di.TafsiliID";
			$colspan = 4;
		}
		else if(isset($_REQUEST["Tafsili2Group"]))
		{
			$query .= ",di.TafsiliID2";
			$colspan = 4;
		}
		else
			$colspan = 3;
	}
	//-----------------------------------------------------
	
	$query .= " order by if(DocType=".DOCTYPE_ENDCYCLE.",2,1),DocDate,if(DebtorAmount<>0,0,1),cc.CostCode";
	
	$dataTable = PdoDataAccess::runquery($query, $whereParam);
	BeginReport();
	
	//if($_SESSION["USER"]["UserName"] == "admin")
	//	echo PdoDataAccess::GetLatestQueryString();
	echo '
		<style>
			@media print {.pageBreak {page-break-after:always;height:1px;} }
		</style>
		<style>
			.DocTbl{width:98%;border-collapse: collapse; }
			.DocTbl td {font-family:nazanin;}
			.header {background-color:#ddd}
			.SignTbl {font-family:nazanin; font-weight:bold}
		</style>';
	$curDate = '';
	$index = 0;
	$curIsEndDoc = '';
	foreach($dataTable as $row)
	{
		if($curDate != $row["DocDate"] || $curIsEndDoc != $row["IsEndDoc"])
		{
			$index++;
			$curDate = $row["DocDate"];
			$curIsEndDoc = $row["IsEndDoc"];
			
			if($index > 1)
			{
				echo '<tr class="header">
					<td colspan="'.$colspan.'">جمع : 
					' . ($CSUM != $DSUM ? "<span style=color:red>سند تراز نمی باشد</span>" :
					CurrencyModulesclass::CurrencyToString($CSUM) . " ریال " ) . '</td>

						<td>' . number_format($DSUM, 0, '.', ',') . '</td>
						<td>' . number_format($CSUM, 0, '.', ',') . '</td>
					</tr>';
				echo '	</table>
						<br>
						<div align="center" style="float:right;width:50%;" class=SignTbl>
						امضاء مسئول حسابداری
						</div>
						<div align="center" style="float:left;width:50%;" class=SignTbl>
						امضاء مدیر عامل
						</div>						
				';
				echo "<div class=pageBreak></div>";
			}
			echo '<table class="DocTbl" border="1" cellspacing="0" cellpadding="2">
				<thead>
				<tr>
					<td colspan='.($colspan+2).'>
						<table style="width:100%">
						<tr>
						<td style="width:25%"><img src="/framework/icons/logo.jpg" style="width:70px" /></td>
						<td style="height: 60px;font-family:titr;font-size:16px" align="center">سند حسابداری</td>
						<td style="width:25%" align="center" >شماره سریال :'. $index .
							'<br>تاریخ سند :'. DateModules::miladi_to_shamsi($row["DocDate"]).'</td>
						</tr>
						</table>
					</td>
				</tr>
				<tr class="header">
					<td align="center" >کد حساب</td>
					<td align="center" >شرح حساب</td>
					<td align="center" >شرح ردیف</td>' . 
					(isset($_REQUEST["TafsiliGroup"]) ? '<td align="center" >تفصیلی</td>' : '') .
					(isset($_REQUEST["Tafsili2Group"]) ? '<td align="center" >تفصیلی2</td>' : '') .
					'<td align="center" >بدهکار</td>
					<td align="center" >بستانکار</td>
				</tr>
				</thead>';
			$DSUM = 0;
			$CSUM = 0;
		}
		$DSUM += $row["DSUM"];
		$CSUM += $row["CSUM"];
		echo "<tr>
				<td >" . $row["CostCode"] . "</td>
				<td >" . $row["CostDesc"] . "</td>
				<td >" . $row["details"] . "</td>" . 
				(isset($_REQUEST["TafsiliGroup"]) ? "<td >" . $row["TafsiliDesc"] . "</td>" : "") .
				(isset($_REQUEST["Tafsili2Group"]) ? "<td >" . $row["TafsiliDesc2"] . "</td>" : "") .
				"<td >" . number_format($row["DSUM"]) . "</td>
				<td >" . number_format($row["CSUM"]) . "</td>
			</tr>";
	}
	echo '<tr class="header">
		<td colspan="' . $colspan . '">جمع : 
		' . ($CSUM != $DSUM ? "<span style=color:red>سند تراز نمی باشد</span>" :
		CurrencyModulesclass::CurrencyToString($CSUM) . " ریال " ) . '</td>

			<td>' . number_format($DSUM, 0, '.', ',') . '</td>
			<td>' . number_format($CSUM, 0, '.', ',') . '</td>
		</tr>';
	echo '	</table>
			<br>
			<div align="center" style="float:right;width:50%;" class=SignTbl>
			امضاء مسئول حسابداری
			</div>

			<div align="center" style="float:left;width:50%;" class=SignTbl>
			امضاء مدیر عامل
			</div>
			<br><br><br><br>
	';
	die();
}
?>
<script>
AccReport_printDocs.prototype = {
	TabID : '<?= $_REQUEST["ExtTabID"]?>',
	address_prefix : "<?= $js_prefix_address?>",

	get : function(elementID){
		return findChild(this.TabID, elementID);
	}
}

AccReport_printDocs.prototype.showReport = function(btn, e)
{
	this.form = this.get("mainForm")
	this.form.target = "_blank";
	this.form.method = "POST";
	this.form.action =  this.address_prefix + "PrintDocs.php?show=true";
	this.form.submit();
	this.get("excel").value = "";
	return;
}

function AccReport_printDocs()
{
	this.formPanel = new Ext.form.Panel({
		renderTo : this.get("main"),
		frame : true,
		layout :{
			type : "table",
			columns :2
		},
		bodyStyle : "text-align:right;padding:5px",
		title : "چاپ تجمیعی اسناد",
		defaults : {
			labelWidth :120
		},
		width : 600,
		items :[{
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
			xtype : "container",
			colspan : 2,
			html : "<input type=checkbox name=TafsiliGroup>به تفکیک تفصیلی 1"
		},{
			xtype : "container",
			colspan : 2,
			html : "<input type=checkbox name=Tafsili2Group> به تفکیک تفصیلی 2"
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
					AccReport_printDocsObj.get('excel').value = "true";
				}
			},
			iconCls : "excel"
		},{
			text : "پاک کردن گزارش",
			iconCls : "clear",
			handler : function(){
				AccReport_printDocsObj.formPanel.getForm().reset();
				AccReport_printDocsObj.get("mainForm").reset();
			}			
		}]
	});
}

AccReport_printDocsObj = new AccReport_printDocs();
</script>
<form id="mainForm">
	<center><br>
		<div id="main" ></div>
	</center>
	<input type="hidden" name="excel" id="excel">
</form>