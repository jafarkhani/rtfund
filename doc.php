<?php

$a = array(   0=> "3", 1 => "d", 6 => "4");
echo count($a);
die();

require_once "framework/header.inc.php";

?>
<META http-equiv=Content-Type content="text/html; charset=UTF-8" ><body dir="rtl">
<?
if(isset($_POST["docNo"]))
{
	$dt = PdoDataAccess::runquery("select * from ACC_docs where BranchID=? and LocalNo=? AND CycleID=1396",
			array($_POST["branch"], $_POST["docNo"]));
	if(count($dt) == 0)
		echo "سند پیدا نشد";
	else
	{
		$status = isset($_POST["confirm"]) ? 200 : 100;
		PdoDataAccess::runquery("update ACC_docs set StatusID=? where DocID=?",
				array($status, $dt[0]["DocID"]));
		if(ExceptionHandler::GetExceptionCount() == 0)
			echo "عملیات با موفقیت انجام شد.";
		else
			echo "عملیات با شکست مواجه شد.";
	}
}

function merging($main,$sub){
	
	$dtmain = PdoDataAccess::runquery("select PersonID,concat_ws(' ',fname,lname,CompanyName) fullname from BSC_persons where PersonID=?",array($main));
	$dtsub = PdoDataAccess::runquery("select PersonID,concat_ws(' ',fname,lname,CompanyName) fullname from BSC_persons where PersonID=?",array($sub));
	
	if(count($dtmain) == 0 || count($dtsub) == 0)
	{
		echo "یکی از کد ها نا معتبر است";
	}
	else
	{
		$PersonID1 = $main; 
		$PersonID2 = $sub;
		echo $dtmain[0]["fullname"] . "<br>" . $dtsub[0]["fullname"] . "<br>";
				
		PdoDataAccess::runquery("update LON_requests set LoanPersonID=? where LoanPersonID=?", 	array($PersonID1, $PersonID2));
		echo "update LON_requests : " . PdoDataAccess::AffectedRows() . "<br>";
		PdoDataAccess::runquery("update BSC_OrgSigners set PersonID=? where PersonID=?", array($PersonID1, $PersonID2));
		echo "update BSC_OrgSigners : " . PdoDataAccess::AffectedRows() . "<br>";
		PdoDataAccess::runquery("update BSC_PersonExpertDomain set PersonID=? where PersonID=?", array($PersonID1, $PersonID2));
		echo "update BSC_PersonExpertDomain : " . PdoDataAccess::AffectedRows() . "<br>";
		PdoDataAccess::runquery("update BSC_licenses set PersonID=? where PersonID=?", array($PersonID1, $PersonID2));
		echo "update BSC_licenses : " . PdoDataAccess::AffectedRows() . "<br>";
		PdoDataAccess::runquery("update CNT_contracts set PersonID=? where PersonID=?", array($PersonID1, $PersonID2));
		echo "update CNT_contracts : " . PdoDataAccess::AffectedRows() . "<br>";
		PdoDataAccess::runquery("update DMS_packages set PersonID=? where PersonID=?", array($PersonID1, $PersonID2));
		echo "update DMS_packages : " . PdoDataAccess::AffectedRows() . "<br>";
		PdoDataAccess::runquery("update PLN_experts set PersonID=? where PersonID=?", array($PersonID1, $PersonID2));
		echo "update PLN_experts : " . PdoDataAccess::AffectedRows() . "<br>";
		PdoDataAccess::runquery("update PLN_plans set PersonID=? where PersonID=?", array($PersonID1, $PersonID2));
		echo "update PLN_plans : " . PdoDataAccess::AffectedRows() . "<br>";
		PdoDataAccess::runquery("update DataAudit set PersonID=? where PersonID=?", array($PersonID1, $PersonID2));
		echo "update DataAudit : " . PdoDataAccess::AffectedRows() . "<br>";
		PdoDataAccess::runquery("update WAR_requests set PersonID=? where PersonID=?", array($PersonID1, $PersonID2));
		echo "update WAR_requests : " . PdoDataAccess::AffectedRows() . "<br>";
		
		PdoDataAccess::runquery("update DMS_documents set ObjectID=? where ObjectType='person' AND ObjectID=?", array($PersonID1, $PersonID2));
		echo "update DMS_documents : " . PdoDataAccess::AffectedRows() . "<br>";
		
		require_once 'framework/person/persons.class.php';
		$obj = new BSC_persons($PersonID1);
		$obj2 = new BSC_persons($PersonID2);
		PdoDataAccess::FillObjectByObject($obj, $obj2);
		$obj->PersonID = $PersonID1;
		$result = $obj->EditPerson();
		echo "copy persons properties : " . ($result ? "true" : "false") . "<br>";
				
		PdoDataAccess::runquery("delete from BSC_persons where PersonID=?", array($PersonID2));
			echo "delete BSC_persons : " . PdoDataAccess::AffectedRows() . "<br>";

		
		$TafsiliID1 = PdoDataAccess::runquery("select * from ACC_tafsilis where TafsiliType=1 AND ObjectID=?",array($main));
		$TafsiliID2 = PdoDataAccess::runquery("select * from ACC_tafsilis where TafsiliType=1 AND ObjectID=?",array($sub));
		if(count($TafsiliID1) == 0 || count($TafsiliID2) == 0)
		{
			echo "یکی از کد ها فاقد تفصیلی است";
		}
		else
		{
			$TafsiliID1 = $TafsiliID1[0]["TafsiliID"];
			$TafsiliID2 = $TafsiliID2[0]["TafsiliID"];
			
			
			PdoDataAccess::runquery("update ACC_DocItems set TafsiliID=? where TafsiliID=?", 
				array($TafsiliID1, $TafsiliID2));
			echo "update ACC_DocItems : " . PdoDataAccess::AffectedRows() . "<br>";
			PdoDataAccess::runquery("update ACC_DocItems set TafsiliID2=? where TafsiliID2=?", 
				array($TafsiliID1, $TafsiliID2));		
			echo "update ACC_DocItems : " . PdoDataAccess::AffectedRows() . "<br>";
			PdoDataAccess::runquery("delete from ACC_tafsilis where TafsiliID=?", array($TafsiliID2));
			echo "delete ACC_tafsilis : " . PdoDataAccess::AffectedRows() . "<br>";
		}
		
		print_r(ExceptionHandler::PopAllExceptions());
	}
}
?>

<form method="post">
شماره سند :	<input type="text" name="docNo">
	<br>
	شعبه
	<select name="branch">
		<option value="3">دانشگاه فردوسی</option>
		<option value="4">پارک</option>
	</select>
	<br>
	<input type="submit" name="confirm" value="تایید">
	<input type="submit" name="raw" value="برگشت از تایید">
</form>
</body>	
