<?php
//-----------------------------
//	Programmer	: SH.Jafarkhani
//	Date		: 94.12
//-----------------------------
 
require_once '../../header.inc.php';
require_once '../global/ManageReport.class.php';
require_once '../../framework/baseInfo/baseInfo.class.php';
require_once inc_CurrencyModule;

$CycleID = isset($_SESSION["accounting"]) ? 
		$_SESSION["accounting"]["CycleID"] : 
		substr(DateModules::shNow(), 0 , 4);


if(isset($_REQUEST["print"]))
{
	BeginReport();
	
	$TafsiliID = !empty($_REQUEST["TafsiliID"]) ? $_REQUEST["TafsiliID"] : "";
	$param = array();
	if($TafsiliID != "")
		$param[] = $TafsiliID;
	
	//-------------- total ------------------
	$query = "select sum(CreditorAmount-DebtorAmount) amount, 
		round(sum(CreditorAmount-DebtorAmount) /" . ShareBaseAmount . ") shareCount
				
	from ACC_DocItems 
		join ACC_docs using(DocID)
		where CostID=" . COSTID_share; /*" AND CycleID=" . $CycleID*/
	$sumRecord = PdoDataAccess::runquery($query, $param);
	if(count($sumRecord) == 0)
	{
		echo "<center><h2>" . "فاقد اطلاعات" . "</h2></center>";
		die(); 
	}
	$sumRecord = $sumRecord[0];
	//------------------------------------------
	
	$query = "select sum(CreditorAmount-DebtorAmount) amount, TafsiliDesc ,
			ShareNo,PersonID,
		round(sum(CreditorAmount-DebtorAmount) /" . ShareBaseAmount . ") shareCount
				
	from ACC_DocItems 
		join ACC_docs using(DocID)
		join ACC_tafsilis using(TafsiliID)
		join BSC_persons on(ObjectID=PersonID)
	where CostID=" . COSTID_share /* . " AND CycleID=" . $CycleID */. 
	($TafsiliID != "" ? " AND TafsiliID=?" : "") .
	" group by TafsiliID";

	$query .= " order by amount desc";
	$dataTable = PdoDataAccess::runquery($query, $param);
	
	if(count($dataTable) == 0)
	{
		echo "<center><h2>" . "فاقد اطلاعات" . "</h2></center>";
		die();
	}
?>

<style>
@media print {
	.pageBreak {page-break-before:always;height:1px;}
}
.page {
	width: 245mm;
	height: 150mm; 
	margin : 2.5cm 2cm 0 2cm; 
}
td {
	font-family: IranNastaliq;
	font-size: 28px;
}
</style>
<?
	$personObj = BSC_jobs::GetModirAmelPerson();
	for($i=0; $i<count($dataTable);$i++)
	{
		echo "<div class=page><table width=100%>
			<tr>
				<td width=160px  style='vertical-align: top'>
					<img style=width:160px  src=/framework/icons/big-logo.png />
				</td>
				<td align=center style='font-size:30px !important'>بسمه تعالی
					<br>برگ سهام " . SoftwareName . "(سهامی خاص)" . "
					<span style=font-size:28px!important;><B><br>سرمایه ثبت شده : " . 
						CurrencyModulesclass::CurrencyToString($sumRecord["amount"]) . " ریال که تماما پرداخت گردیده است
						<br>منقسم به " . CurrencyModulesclass::CurrencyToString($sumRecord["shareCount"]) . " سهم " . 
						CurrencyModulesclass::CurrencyToString(ShareBaseAmount) . " ریالی
					</b><br><br>
					</span>
				</td>
				<td width=160px style='font-family:Homa;font-size: 12px;vertical-align: top'>شناسه ملی: " . OWNER_NATIONALID . "
					<br>شماره ثبت: " . OWNER_REGCODE . "
					<br>تاریخ ثبت: " . OWNER_REGDATE . "
					<br><img width=120 src='/generalClasses/phpqrcode/showQRcode.php?value=سهام " . SoftwareName . 
						" %0D شناسه ملی : 10380491265 %0D سهامدار : " . $dataTable[$i]["TafsiliDesc"] . "%0D ارزش سهام : " . 
						number_format($dataTable[$i]["amount"]) . " ریال " . " %0D شماره سریال: " . $dataTable[$i]["PersonID"] . "' >
				</td>
			</tr>
			<tr>
				<td colspan=3 align=center style='text-align: justify;padding: 0 5 0 5'>
				بدینوسیله گواهی می شود تعداد
				<b><u> " . $dataTable[$i]["shareCount"] . " </u></b>( " . CurrencyModulesclass::CurrencyToString($dataTable[$i]["shareCount"]) . " ) 
				سهم با نام از مجموع 
				<b><u>" . $sumRecord["shareCount"] . "</u></b> ( " . 
				CurrencyModulesclass::CurrencyToString($sumRecord["shareCount"]) . " ) 
				سهم 
				" . SoftwareName . " به ارزش اسمی هر سهم " . 
				"<b><u>" . number_format(ShareBaseAmount) . "</u></b> ریال ( " . CurrencyModulesclass::CurrencyToString(ShareBaseAmount) . " ریال ) و مجموعا به ارزش 
				<b><u>" . number_format($dataTable[$i]["amount"]) . "</u></b> ریال ( " . CurrencyModulesclass::CurrencyToString($dataTable[$i]["amount"]) . " ریال ) 
 به عنوان سهام عادی و با نام متعلق به 
				<b>" . $dataTable[$i]["TafsiliDesc"] . "</b>
				می باشد و سهام مذکور در دفتر ثبت سهام تحت شماره
				<b><u> &nbsp;&nbsp;" . $dataTable[$i]["ShareNo"] . "</u></b> &nbsp;( " . CurrencyModulesclass::CurrencyToString($dataTable[$i]["ShareNo"]) . " ) &nbsp;&nbsp; 
				ثبت گردیده است.
				<br>
				</td>
			</tr>
			<tr>
				<td style=padding-right:40px align=center>" . $personObj->_fullname . "
				<br> مدیر عامل</td>
				<td align=center>مهر صندوق</td>
				<td style=padding-left:40px align=center>دکتر مرتضی خادمی<br> 
				رئیس هیات مدیره</td>
			</tr>
		</table></div>";
		
		echo "<div style='width:285mm;font-family:Homa;font-size:12px'>
			<center>با صدور این برگ اوراق صادره قبلی باطل اعلام می گردد.( تاریخ صدور : " .
			DateModules::shNow(). " )"
				. "<br>برگ سهام بدون هولوگرام و مهر برجسته فاقد اعتبار است</center></div>";
		
		if($i != count($dataTable)-1)
			echo Manage_Report::PageBreak();
	}
	echo "</center></body></html>";
	die();
}

?>
<script>
PrintShare.prototype = {
	TabID : '<?= $_REQUEST["ExtTabID"]?>',
	address_prefix : "<?= $js_prefix_address?>",

	DocID : "",

	get : function(elementID){
		return findChild(this.TabID, elementID);
	}
}

function PrintShare()
{
	this.mainPanel = new Ext.form.Panel({
		renderTo : this.get("main"),
		frame : true,
		bodyStyle : "text-align:right;padding:5px",
		title : "چاپ برگه سهام",
		width : 400,
		items :[{
			xtype : "combo",
			width : 380,
			store: new Ext.data.Store({
				fields:["TafsiliID","TafsiliDesc"],
				proxy: {
					type: 'jsonp',
					url: '/accounting/baseinfo/baseinfo.data.php?task=GetAllTafsilis&TafsiliType=1&Shareholder=true',
					reader: {root: 'rows',totalProperty: 'totalCount'}
				}
			}),
			emptyText:'انتخاب تفصیلی ...',
			typeAhead: false,
			pageSize : 10,
			valueField : "TafsiliID",
			itemId : "TafsiliID",
			displayField : "TafsiliDesc"			
		}],
		buttons : [{
			text : "چاپ برگه سهام",
			iconCls : "print",
			handler : function()
			{
				str = PrintShareObj.mainPanel.down("[itemId=TafsiliID]").getValue() != null ?
					"&TafsiliID=" + PrintShareObj.mainPanel.down("[itemId=TafsiliID]").getValue() : "";
				window.open(PrintShareObj.address_prefix + "PrintShare.php?print=true" + str);
			}
		}]
	});
}

PrintShareObj = new PrintShare();

</script>
<form id="mainForm">
	<center><br>
		<div id="main" ></div><br>
		<div><div id="regDocDIV"></div></div>
	</center>
</form>
