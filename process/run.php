<?php
//---------------------------
// programmer:	Jafarkhani
// create Date: 97.12
//---------------------------
 
require_once '../header.inc.php';
require_once '../commitment/ExecuteEvent.class.php';
require_once '../loan/request/request.class.php';

ini_set("display_errors", "On");
ini_set('max_execution_time', 30000);
ini_set('memory_limit','2000M');
header("X-Accel-Buffering: no");
ob_start();
set_time_limit(0);
error_reporting(0);

global $GFromDate;
$GFromDate = '2019-03-21'; //1398/01/01
global $GToDate;
$GToDate = '2019-10-19'; //1398/07/27

$reqs = PdoDataAccess::runquery_fetchMode(" select BackPayID,RequestID from LON_BackPays where BackPayID in(

12050,
12310,
12944,
9304,
8743,
7299,
12223,
12176,
11613,
11887,
11888,
12992,
13555,
11889,
12351,
7237,
7242,
12562,
12899,
12053,
12543,
7770,
11978,
12356,
12476,
7971,
11880,
12095,
11806,
12162,
8485,
11905,
12046,
12047,
8400,
8401,
13333,
11827,
12088,
12230,
12657,
13304,
11821,
11918,
12143,
12348,
12684,
12685,
11902,
12085,
12292,
8656,
11921,
12131,
12314,
12616,
12914,
9273,
11396,
11881,
12094,
12566,
12808,
11855,
12844,
12090,
11884,
13039,
13044,
12560,
12021,
12086,
12258,
12571,
12588,
12541,
13104,
9810,
9819,
9820,
9821,
9822,
9823,
12217,
12218,
12717,
13066,
12656,
10984,
11282,
11386,
12843,
13047,
11647,
13837,
11784,
11785,
11786,
11787,
12538,
11865,
12802

)
	");
//echo PdoDataAccess::GetLatestQueryString();
$pdo = PdoDataAccess::getPdoObject();

$DocObj = array();

while($requset=$reqs->fetch())
{
	$pdo->beginTransaction();
	echo "-------------- " . $requset["RequestID"] . " -----------------<br>";
	ob_flush();flush();
	
	$result = true;
	$backpays = PdoDataAccess::runquery(
			"select * from LON_BackPays
				left join ACC_IncomeCheques i using(IncomeChequeID) 
				where BackPayID=? AND PayDate>='$GFromDate'
			AND if(PayType=" . BACKPAY_PAYTYPE_CHEQUE . ",ChequeStatus=".INCOMECHEQUE_VOSUL.",1=1)
			order by PayDate", array($requset["BackPayID"]));
	
	$reqObj = new LON_requests($requset["RequestID"]);
	$partObj = LON_ReqParts::GetValidPartObj($requset["RequestID"]);
	
	foreach($backpays as $bpay)
	{
		if($reqObj->ReqPersonID*1 > 0)
		{
			if($reqObj->FundGuarantee == "YES")
			{
				if($bpay["IncomeChequeID"]*1 > 0)
					$EventID = EVENT_LOANBACKPAY_agentSource_committal_cheque;
				else
					$EventID = EVENT_LOANBACKPAY_agentSource_committal_non_cheque;
			}
			else
			{
				if($bpay["IncomeChequeID"]*1 > 0)
					$EventID = EVENT_LOANBACKPAY_agentSource_non_committal_cheque;
				else
					$EventID = EVENT_LOANBACKPAY_agentSource_non_committal_non_cheque;
			}
		}
		else
		{
			if($bpay["IncomeChequeID"]*1 > 0)
				$EventID = EVENT_LOANBACKPAY_innerSource_cheque;
			else
				$EventID = EVENT_LOANBACKPAY_innerSource_non_cheque;
		}
		$eventobj = new ExecuteEvent($EventID);
		$eventobj->Sources = array($reqObj->RequestID, $partObj->PartID, $bpay["BackPayID"]);
		$eventobj->DocDate = $bpay["PayDate"];
		$result = $eventobj->RegisterEventDoc($pdo);
	}
	echo "بازپرداخت : " . ($result ? "true" : "false") . "<br>";
	if(ExceptionHandler::GetExceptionCount() > 0)
	{
		print_r(ExceptionHandler::PopAllExceptions());
		$pdo->rollBack();
		return;
	}
	ob_flush();flush();

	$pdo->commit();

	EventComputeItems::$LoanComputeArray = array();
	EventComputeItems::$LoanPuresArray = array();
}

?>
