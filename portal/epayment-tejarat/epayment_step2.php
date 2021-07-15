<?php
//-------------------------
// programmer:	Jafarkhani
// Create Date:	97.06
//-------------------------
require_once '../header.inc.php';
require_once getenv("DOCUMENT_ROOT") . '/loan/request/request.class.php';
require_once getenv("DOCUMENT_ROOT") . '/commitment/ExecuteEvent.class.php';

require_once 'nusoap.php';

ini_set("display_errors", "On");

function messeg2($result)
{
	switch ($result) 
	{
		case '-20':
			$ErrorDesc = "در درخواست کارکتر های غیر مجاز وجو دارد";
			break;
		case '-30':
			$ErrorDesc = " تراکنش قبلا برگشت خورده است";
			break;
		case '-50':
			$ErrorDesc = " طول رشته درخواست غیر مجاز است";
			break;
		case '-51':
			$ErrorDesc = " در در خواست خطا وجود دارد";
			break;
		case '-80':
			$ErrorDesc = " تراکنش مورد نظر یافت نشد";
			break;
		case '-81':
			$ErrorDesc = " خطای داخلی بانک";
			break;
		case '-90':
			$ErrorDesc = " تراکنش قبلا تایید شده است";
			break;
	}
	
	echo "<p align=center dir=rtl><font color=red face=tahoma>";
	echo $ErrorDesc;
	echo "</font></p>";
}
function messeg($resultCode)
{
	switch ($resultCode) 
	{
		case 110:
			$ErrorDesc = " انصراف دارنده کارت";
			break;
		case 120:
			$ErrorDesc = "   موجودی کافی نیست";
			break;
		case 130:
		case 131:
		case 160:
			$ErrorDesc = "   اطلاعات کارت اشتباه است";
			break;
		case 132:
		case 133:
			$ErrorDesc = "   کارت مسدود یا منقضی می باشد";
			break;
		case 140:
			$ErrorDesc = " زمان مورد نظر به پایان رسیده است";
			break;
		case 200:
		case 201:
		case 202:
			$ErrorDesc = " مبلغ بیش از سقف مجاز";
			break;
		case 166:
			$ErrorDesc = " بانک صادر کننده مجوز انجام  تراکنش را صادر نکرده";
			break;
		case 150:
		default:
			$ErrorDesc = " خطا بانک  $resultCode";
		break;
	}
	echo "<p align=center dir=rtl><font color=red face=tahoma>";
	echo $ErrorDesc;
	echo "</font></p>";
}
function RegDoc($RequestID, $amount, $PayRefNo){
	
	$dt = PdoDataAccess::runquery("select * from LON_BackPays where PayRefNo=?", array($PayRefNo));
	if(count($dt) > 0)
	{
		ExceptionHandler::PushException("<br> کد رهگیری قبلا ثبت شده است");
		return false;
	}
	
	$obj = new LON_BackPays();
	$obj->RequestID = $RequestID;
	$obj->PayType = 4;
	$obj->PayAmount = $amount;
	$obj->PayDate = PDONOW;
	$obj->PayRefNo = $PayRefNo;

	$pdo = PdoDataAccess::getPdoObject();
	$pdo->beginTransaction();

	if(!$obj->Add($pdo))
		return false;

	$reqObj = new LON_requests($RequestID);
	$partObj = LON_ReqParts::GetValidPartObj($RequestID);
	$EventID = LON_requests::GetEventID($RequestID, EVENTTYPE_LoanBackPay);
	$eventobj = new ExecuteEvent($EventID);
	$eventobj->Sources = array($RequestID, $partObj->PartID, $obj->BackPayID);
	
	$eventobj->tafsilis = array();
	$eventobj->tafsilis[TAFSILITYPE_PERSON] = 1947; //تجارت کوتاه مدت
	
	$result = $eventobj->RegisterEventDoc($pdo);
	if(!$result)
	{
		$pdo->rollBack();
		return false;
	}
	
	$pdo->commit();
	return true;	
}

$result = "";
if (!isset($_REQUEST["resultCode"])) {
	$result = "تراکنش بی نتیجه";
}
else if ($_REQUEST["resultCode"] == 100) {

	$referenceId = isset($_POST['referenceId']) ? $_POST['referenceId'] : 0;
	
	$params = array();
	$params['token'] = $_SESSION['token'];
	$params['merchantId'] = BANK_TEJARAT_MERCHANTID;
	$params['referenceNumber'] = $referenceId;
	$params['sha1Key'] = BANK_TEJARAT_SHALKEY;
	
	$dt = PdoDataAccess::runquery("select * from ACC_EPays where PayID=?", array($_POST['paymentId']));
	if(count($dt) == 0)
	{
		$result = "خطا در انتقال پارامترهای بانک";
	}
	else
	{
		$PayObj = new ACC_EPays($dt[0]["PayID"]);
		$client = new SoapClient('https://ikc.shaparak.ir/XVerify/Verify.xml', array('soap_version'   => SOAP_1_1));
		$result = $client->__soapCall("KicccPaymentsVerification", array($params));
		$result = ($result->KicccPaymentsVerificationResult);
		
		if (floatval($result) > 0 && floatval($result) == floatval($_SESSION['amount']) )
		{	
			$PayObj->StatusCode = $_REQUEST["resultCode"];
			$PayObj->PayRefNo = $referenceId;
			$PayObj->Edit();
			$DocRegResult = RegDoc($PayObj->RequestID, $PayObj->amount, $referenceId);
			if(!$DocRegResult)
			{
				$PayObj->error = ExceptionHandler::GetExceptionsToString();
				$PayObj->Edit();
			}
			
			$result = "پرداخت الكترونيكي شما به درستي انجام گرفت. شماره رسيد بانكي زير براي شما صادر گرديده است: </p>";
			$result .= "<table width=80% align=center border=1 cellspacing=0 cellpadding=5 dir=rtl>
				<tr>
					<td>مبلغ پرداختي: </td>
					<td><b>" . number_format($PayObj->amount) . "</b> ریال  </td>
				</tr>
				<tr>
					<td> شماره پیگیری: </td>
					<td dir=ltr align=right><b>" . $referenceId . "</b></td>
				</tr>
			</table>";	
			
		}else
		{
			$result = "<br> عملیات پرداخت قسط به درستی ثبت نگردیده است. " . 
					"<br> وجه کسر شده حداکثر تا 72 ساعت به حساب شما برگشت خواهد شد." . "<br>";
			$result .= messeg2($result);
		}	
	}
}
else 
{
	$result = $msg = messeg($_POST['resultCode']) . "<br>";
	
	if (isset($_POST['referenceId'])) {
		$result .= '<p align=center><font face=tahoma size=3>';
		$result .= 'کد پیگیری:' . $_POST['referenceId'];
		$result .= '</font></p>';
	}
}
?>
<html>
	<head>
		<meta content="text/html; charset=utf-8" http-equiv="Content-Type"/>	
		<style>
			body{
				font-family: tahoma;
				font-size: 12px;
				font-weight: bold;
			}
			button{
				font-family: tahoma;
				font-size: 12px;
			}
		</style>
	</head>	
	<body dir="rtl">
	<center>
		<br>
		<br>
		<div style="width: 500px; border: 1px solid #99BBE8; background-color: #DFE9F6; padding:5px;border-radius: 4px; height: 220px;">
			<div style="background-color: white; width: 500px; height:80%">
				<br>
				<?= $result ?>
				<br>&nbsp;
			</div>
			<br><button style="" onclick="window.opener.location.reload(); window.close()">
				بازگشت به پرتال   <?= SoftwareName ?>
			</button>
			<br>&nbsp;
		</div>
	</center>
</body>
</html>