<?php
//-----------------------------
//	Programmer	: M.Mokhtari
//	Date		: 99.2
//-----------------------------

require_once '../../header.inc.php';
require_once "ReportGenerator.class.php";
require_once '../request/request.class.php'; 
require_once '../request/request.data.php';
require_once '../../framework/person/persons.class.php';
ini_set("display_errors", "On");

if(isset($_REQUEST["show"]))
{
	$RequestID = $_REQUEST["RequestID"];
	$ReqObj = new LON_requests($RequestID);
	$person = new BSC_persons($ReqObj->LoanPersonID);

	if(isset($person->CompanyName) && !empty($person->CompanyName)){
	    $loanGetterType = 'company';
	}
	if(isset($person->fname) && !empty($person->fname) && isset($person->lname) && !empty($person->lname)){
	    $loanGetterType = 'person';
	}
	$CompanyTypeName = '';
	$CompanyNationalID = '';
	$PersonNationalID = '';
	$RegPlace = '';
	$CompanyAddress ='';
	$PersonAddress = '';
	$CompanyPhoneNo = '';
	$PersonPhoneNo = '';
	
	if($loanGetterType == 'company'){
	    $CompanyTypeID=$person->CompanyType;
	    $tempCmpType = PdoDataAccess::runquery_fetchMode("select InfoDesc from BaseInfo where typeID=14 AND IsActive='YES' AND InfoID=". $CompanyTypeID ." order by InfoDesc");
	    $CompanyType = $tempCmpType->fetchAll();
	    if(isset($CompanyType) && !empty($CompanyType))
	    $CompanyTypeName = $CompanyType[0]['InfoDesc'];
	    $CompanyNationalID = $person->NationalID;
	    $RegPlace = $person->RegPlace;
	    $CompanyAddress = $person->address;
	    $CompanyPhoneNo = $person->PhoneNo;
	}
	if($loanGetterType == 'person'){
	    $PersonNationalID = $person->NationalID;
	    $PersonAddress = $person->address;
	    $PersonPhoneNo = $person->PhoneNo;
	}
	
	$partObj = LON_ReqParts::GetValidPartObj($RequestID);
	//............ get total loan amount ......................
	$TotalAmount = LON_installments::GetTotalInstallmentsAmount($RequestID);
		
	$firstQuery = "select RequestID,InstallmentAmount, max(InstallmentDate) LastInstallmentDate , min(InstallmentDate) FirstInstallmentDate
				from LON_installments
				where RequestID=". $RequestID ." ";
	$firstTemp = PdoDataAccess::runquery_fetchMode($firstQuery);
	$InstallmentsAmounts = $firstTemp->fetchAll();
	$FirstInstallmentDate = '';
	$LastInstallmentDate = '';
	if(!empty($InstallmentsAmounts)){
	    $FirstInstallmentDate = $InstallmentsAmounts[0]['FirstInstallmentDate'];
	    $LastInstallmentDate =  $InstallmentsAmounts[0]['LastInstallmentDate'];
	}
	
	$secoundQuery = "select RequestID,sum(PayAmount) TotalPayAmount , max(PayDate) MaxPayDate ,PayAmount lastPayAmount
				from LON_BackPays where RequestID=". $RequestID ." ";
	$secoundTemp = PdoDataAccess::runquery_fetchMode($secoundQuery);
	$cnt = $secoundTemp->rowCount();
	$BackPaysAmounts = $secoundTemp->fetchAll();
	$MaxPayDate = '';
	$TotalPayAmount = '';
	$lastPayAmount ='';
	if(!empty($BackPaysAmounts)){
	    $MaxPayDate = $BackPaysAmounts[0]['MaxPayDate'];
	    $TotalPayAmount = $BackPaysAmounts[0]['TotalPayAmount'];
	    $lastPayAmount = $BackPaysAmounts[0]['lastPayAmount'];
	}
	
	//............ get remain untill now ......................
	$ComputeDate = !empty($_REQUEST["ComputeDate"]) ? $_REQUEST["ComputeDate"] : "";
	$ComputePenalty = !empty($_REQUEST["ComputePenalty"]) && $_REQUEST["ComputePenalty"] == "false" ? 
			false : true;
	$ComputeArr = LON_Computes::ComputePayments($RequestID, $ComputeDate, null, $ComputePenalty);
	
	$PureArr = LON_Computes::ComputePures($RequestID); 
	//............ get remain untill now ......................
	$CurrentRemain = LON_Computes::GetCurrentRemainAmount($RequestID, $ComputeArr);
	$TotalRemain = LON_Computes::GetTotalRemainAmount($RequestID, $ComputeArr);
	$remains = LON_Computes::GetRemainAmounts($RequestID, $ComputeArr);
	//............. get total payed .............................
	$dt = LON_BackPays::GetRealPaid($RequestID);
	$totalPayed = 0;
	foreach($dt as $row)
		$totalPayed += $row["PayAmount"]*1;
	//............................................................
	if($ReqObj->IsEnded == "YES")
	{
		$CurrentRemain = "وام خاتمه یافته";
		$TotalRemain = "وام خاتمه یافته";
	}
	else if($ReqObj->StatusID == LON_REQ_STATUS_DEFRAY)
	{
		$CurrentRemain = "وام تسویه شده است";
		$TotalRemain = "وام تسویه شده است";
	}
	else
	{
		$CurrentRemain = number_format($CurrentRemain) . " ریال";
		$TotalRemain = number_format($TotalRemain) . " ریال";
		$DefrayAmount = number_format($DefrayAmount) . " ریال";
	}
	//............................................................
	
	
	BeginReport();
	echo "<table style='border:2px groove #9BB1CD;border-collapse:collapse;width:100%'><tr>
			<td width=60px><img src='/framework/icons/logo.jpg' style='width:120px'></td>
			<td align='center' style='height:100px;vertical-align:middle;font-family:titr;font-size:15px'>
				گزارش وام برای استمهال
			</td>
			<td width='200px' align='center' style='font-family:tahoma;font-size:11px'>تاریخ تهیه گزارش : " 
		. DateModules::shNow() . "<br>";
	
	echo "</td></tr></table>";
	
	//..........................................................
	
	?>
	<style>
	    .newTable  td, th {
        border: 1px solid #dddddd;
        text-align: right;
        padding: 8px;
	    }
	    .newTable  th {
        color: rgb(21, 66, 139);
        background-color: rgb(189, 211, 239);
	    }
	    .newTable  td{
        text-align: center;
	    }
	    .newTable table{
	        border:2px groove #9BB1CD;border-collapse:collapse;width:100%;font-family: nazanin;
		   font-size: 16px;line-height: 20px;
	    }
	   
	    
	</style>

	<div class="newTable">
	    	<table>
   <!-- <caption><h2>اطلاعات تسهیلات برای استمهال</h2></caption>-->
    
    <tr>
        <th style="background-color: rgb(189, 205, 229)" rowspan="7">مشخصات شخص حقوقی</th>
    <tr><th>نام شرکت</th><td>  <?= $person->CompanyName  ?>  </td>
        <th>نام و نام خانوادگی نماینده شرکت</th> <td>   <?= $ReqObj->_LoanPersonFullname  ?> </td></tr>

    <tr><th>نوع شخصیت حقوقی</th><td> <?= $CompanyTypeName ?> </td>
    <th>شماره تماس نماینده شرکت</th> <td> <?=  $person->mobile ?> </td></tr>
    
    <tr><th>شناسه ملی</th><td> <?= $CompanyNationalID ?> </td>
        <th>محل ثبت</th> <td> <?= $RegPlace ?> </td>  </tr>
    
    <tr><th>وضعیت فعالیت فعلی</th><td> فعال &#x25A2  نیمه تعطیل &#x25A2  غیرفعال &#x25A2   </td>
        <th>تلفن شرکت</th> <td> <?= $CompanyPhoneNo ?> </td></tr>
    
    <tr><th>آدرس کنونی شرکت</th><td> <?= $CompanyAddress ?> </td></tr>
    
    <tr><th>نوع شرکت</th><td> مرکز رشد پارک &#x25A2  مرکز رشد دانشگاه &#x25A2  دانش بنیان &#x25A2  هیچکدام &#x25A2   </td></tr>
    </tr>
    
    <tr>
        <th style="background-color: rgb(189, 205, 229)" rowspan="4">مشخصات شخص حقیقی</th>
    <tr><th>نام و نام خانوادگی</th><td><?= $person->fname ?> <?=$person->lname ?></td>
        <th>شماره ملی</th> <td> <?= $PersonNationalID ?> </td></tr>

    <tr><th>شماره تماس</th><td> <?= $PersonPhoneNo ?></td>
        <th>آدرس</th> <td> <?= $PersonAddress ?> </td></tr>
    
    <tr><th>وضعیت فعالیت فعلی</th><td> فعال&#x25A2    نیمه تعطیل&#x25A2    غیرفعال&#x25A2     </td></tr>
    </tr>

    <tr>
        <th style="background-color: rgb(189, 205, 229)" rowspan="8">اطلاعات قرارداد و متمم ها</th>
        <tr><th>موضوع طرح</th><td>  <?= $ReqObj->PlanTitle ?> </td></tr>
    
        <tr><th>شماره قرارداد</th><td> <?= $RequestID ?> </td>
        <th>تاریخ قرارداد</th><td> <?= DateModules::miladi_to_shamsi($partObj->PartDate) ?> </td></tr>
        
        <tr><th>مبلغ تسهیلات پرداخت شده به ریال</th><td> <?= number_format($partObj->PartAmount) ?> </td>
        <th>تاریخ پرداخت</th> <td> <?= DateModules::miladi_to_shamsi($partObj->PartDate) ?> </td></tr>
    
        <tr><th>مدت زمان تنفس</th><td> <?= $partObj->DelayMonths  ?>ماه و  <?= $partObj->DelayDays ?> روز </td>
        <th>تعداد اقساط</th> <td> <?= $partObj->InstallmentCount ?> </td></tr>
        
        
        <tr><th>کارمزد تسهیلات</th><td> <?= $partObj->CustomerWage ?> % </td>
        <th>کارمزد دیرکرد</th> <td> <?= $partObj->LatePercent ?> % </td></tr>
        
        <tr><th>تاریخ سررسید اولین قسط</th><td> <?= DateModules::miladi_to_shamsi($FirstInstallmentDate) ?> </td>
        <th>تاریخ سررسید آخرین قسط</th> <td> <?= DateModules::miladi_to_shamsi($LastInstallmentDate) ?> </td></tr>
        
        <tr><th>مدت تمدید تسهیلات</th><td></td>
        <th>تعداد دفعات تمدید تسهیلات</th><td></td></tr>
    </tr>
    
    
    <tr>
      <th style="background-color: rgb(189, 205, 229)" rowspan="3">پرداختهای مشتری</th>
        <tr><th>مبلغ کل پرداخت شده (ریال)</th><td> <?= number_format($TotalPayAmount) ?> </td>

        <th>تاریخ آخرین پرداخت </th><td> <?= DateModules::miladi_to_shamsi($MaxPayDate) ?> </td></tr>  
        
        <tr><th> آخرین مبلغ پرداخت (ریال)</th><td> <?= number_format($lastPayAmount) ?> </td></tr>
    </tr>
    
    
    <tr>
      <th style="background-color: rgb(189, 205, 229)" rowspan="3">میزان بدهی ها</th>
        <tr><th> مبلغ بدهی سررسید گذشته (ریال)</th><td> <?= $CurrentRemain ?> </td>
        <th>مبلغ پرداخت شده (ریال)</th><td> <?= number_format($TotalPayAmount) ?> </td></tr>  
        
        <tr><th>مانده تا انتها(ریال)</th><td> <?= $TotalRemain ?> </td>
        <th>مانده جریمه تاخیر(ریال)</th><td> <?= number_format($remains["remain_pnlt"]) ?> </td></tr>       
    </tr>


</table>
	</div>

	
	
	
	<?
	
	
}
?>
<script>
LoanReport_payments.prototype = {
	TabID : '<?= $_REQUEST["ExtTabID"]?>',
	address_prefix : "<?= $js_prefix_address?>",

	get : function(elementID){
		return findChild(this.TabID, elementID);
	}
}

LoanReport_payments.prototype.showReport = function(btn, e)
{
	this.form = this.get("mainForm")
	this.form.target = "_blank";
	this.form.method = "POST";
	this.form.action =  this.address_prefix + "LoanMoratorium.php?show=true";
	this.form.submit();
	this.get("excel").value = "";
	return;
}

/*LoanReport_payments.prototype.showReport2 = function(btn, e)
{
	this.form = this.get("mainForm")
	this.form.target = "_blank";
	this.form.method = "POST";
	this.form.action =  this.address_prefix + "DebitReport.php?show=true";
	this.form.submit();
	this.get("excel").value = "";
	return;
}*/

function LoanReport_payments()
{
	this.formPanel = new Ext.form.Panel({
		renderTo : this.get("main"),
		frame : true,
		layout :{
			type : "table",
			columns :1 
		},
		bodyStyle : "text-align:right;padding:5px",
		title : "گزارش استمهال وام",
		defaults : {
			labelWidth :120
		},
		width : 650,
		items :[{
			xtype : "combo",
			store: new Ext.data.Store({
				proxy:{
					type: 'jsonp',
					url: this.address_prefix + '../request/request.data.php?task=SelectAllRequests2',
					reader: {root: 'rows',totalProperty: 'totalCount'}
				},
				fields :  ['loanFullname','PartAmount',"RequestID","PartDate", "ReqDate","RequestID",{
					name : "fullTitle",
					convert : function(value,record){
						return "[ " + record.data.RequestID + " ] " + 
							record.data.loanFullname + "  به مبلغ  " + 
							Ext.util.Format.Money(record.data.PartAmount) + " مورخ " + 
							MiladiToShamsi(record.data.PartDate);
					}
				}]				
			}),
			displayField: 'fullTitle',
			pageSize : 10,
			valueField : "RequestID",
			hiddenName : "RequestID",
			width : 600,
			tpl: new Ext.XTemplate(
				'<table cellspacing="0" width="100%"><tr class="x-grid-header-ct" style="height: 23px;">',
				'<td style="padding:7px">کد وام</td>',
				'<td style="padding:7px">وام گیرنده</td>',
				'<td style="padding:7px">مبلغ وام</td>',
				'<td style="padding:7px">تاریخ پرداخت</td> </tr>',
				'<tpl for=".">',
					'<tr class="x-boundlist-item" style="border-left:0;border-right:0">',
					'<td style="border-left:0;border-right:0" class="search-item">{RequestID}</td>',
					'<td style="border-left:0;border-right:0" class="search-item">{loanFullname}</td>',
					'<td style="border-left:0;border-right:0" class="search-item">',
						'{[Ext.util.Format.Money(values.PartAmount)]}</td>',
					'<td style="border-left:0;border-right:0" class="search-item">{[MiladiToShamsi(values.PartDate)]}</td> </tr>',
				'</tpl>',
				'</table>'
			),
			itemId : "RequestID"
		}],
		buttons : [{
			text : "مشاهده گزارش",
			handler : Ext.bind(this.showReport,this),
			iconCls : "report"
		}]
	});
}

LoanReport_paymentsObj = new LoanReport_payments();
</script>
<form id="mainForm">
	<center><br>
		<div id="main" ></div>
	</center>
	<input type="hidden" name="excel" id="excel">
</form>