<?php
require_once "header.inc.php";
 
/*require_once("framework/PasswordHash.php");
$dt = PdoDataAccess::runquery("select * from BSC_persons 
	where (UserName='' or UserName is null) AND NationalID<>''");
foreach($dt as $row)
{
	$defaultPass = $row["NationalID"];
	$hash_cost_log2 = 8;	
	$hasher = new PasswordHash($hash_cost_log2, true);
	$newPass = $hasher->HashPassword(md5($defaultPass));
	PdoDataAccess::runquery("update BSC_persons set UserName=?, UserPass=? where PersonID=?", 
		array($row["NationalID"],$newPass, $row["PersonID"]));
	echo $row["PersonID"] . " : " . (PdoDataAccess::GetExceptionCount()>0 ? "false" : "true") . "<br>";
	flush();
	ob_flush();
}
print_r(ExceptionHandler::PopAllExceptions());
die();*/
?>
<META http-equiv=Content-Type content="text/html; charset=UTF-8" ><body dir="rtl">
<?php
if(isset($_POST["submit"]))
{
	merging($_POST["main"],$_POST["sub"]);
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
 کد پرسنلی اصلی : 
	<input type="text" name="main">
	<br>
	کد پرسنلی که باید در اصلی ادغام شود :
	<input type="text" name="sub">
	<br>
	<input type="submit" name="submit">
</form>
</body>	
