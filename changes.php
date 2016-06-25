<?php

/*
ALTER TABLE `krrtfir_rtfund`.`PLN_groups` ADD COLUMN `ScopeID` INTEGER UNSIGNED NOT NULL DEFAULT 1 AFTER `GroupDesc`,
 ADD COLUMN `CustomerRelated` ENUM('YES','NO') NOT NULL DEFAULT 'YES' AFTER `ScopeID`,
 ADD COLUMN `IsMandatory` ENUM('YES','NO') NOT NULL DEFAULT 'NO' AFTER `CustomerRelated`;



insert into DMS_packages(BranchID,PackNo,PersonID)
select BranchID,@i:=@i+1,LoanPersonID from
(select BranchID,LoanPersonID
from LON_requests
where LoanPersonID > 0
group by LoanPersonID,BranchID
order by RequestID)t1,(select @i:=0)t2
;

insert into DMS_PackageItems(PackageID,ObjectType,ObjectID)
select PackageID,1,RequestID from LON_requests r
join DMS_packages p on(p.BranchID=r.BranchID AND r.LoanPersonID=p.PersonID)
where LoanPersonID > 0

 */

require_once "framework/header.inc.php";
?>
<META http-equiv=Content-Type content="text/html; charset=UTF-8" ><body dir="rtl">
<?
if(isset($_POST["submit"]))
{
	merging($_POST["main"],$_POST["sub"]);
}

function merging($mainTafsiliCode,$subTafsiliCode){
	$dtmain = PdoDataAccess::runquery("select * from ACC_tafsilis where TafsiliCode=?",array($mainTafsiliCode));
	$dtsub = PdoDataAccess::runquery("select * from ACC_tafsilis where TafsiliCode=?",array($subTafsiliCode));
	
	if(count($dtmain) == 0 || count($dtsub) == 0)
	{
		echo "یکی از کد ها نا معتبر است";
	}
	else
	{
		echo $dtmain[0]["TafsiliDesc"] . "<br>" . $dtsub[0]["TafsiliDesc"] . "<br>";
		$TafsiliID1 = $dtmain[0]["TafsiliID"];
		$PersonID1 = $dtmain[0]["ObjectID"];
		$TafsiliID2 = $dtsub[0]["TafsiliID"];
		$PersonID2 = $dtsub[0]["ObjectID"];
		
		PdoDataAccess::runquery("update LON_requests set LoanPersonID=? where LoanPersonID=?", 
			array($PersonID1, $PersonID2));
		echo "update LON_requests : " . PdoDataAccess::AffectedRows() . "<br>";
		PdoDataAccess::runquery("delete from BSC_persons where PersonID=?", array($PersonID2));
		echo "delete BSC_persons : " . PdoDataAccess::AffectedRows() . "<br>";
		
		PdoDataAccess::runquery("update ACC_DocItems set TafsiliID=? where TafsiliID=?", 
			array($TafsiliID1, $TafsiliID2));
		echo "update ACC_DocItems : " . PdoDataAccess::AffectedRows() . "<br>";
		PdoDataAccess::runquery("update ACC_DocItems set TafsiliID2=? where TafsiliID2=?", 
			array($TafsiliID1, $TafsiliID2));		
		echo "update ACC_DocItems : " . PdoDataAccess::AffectedRows() . "<br>";
		PdoDataAccess::runquery("delete from ACC_tafsilis where TafsiliID=?", array($TafsiliID2));
		echo "delete ACC_tafsilis : " . PdoDataAccess::AffectedRows() . "<br>";
		
		print_r(ExceptionHandler::PopAllExceptions());
	}
}
?>

<form method="post">
	تفصیلی اصلی : 
	<input type="text" name="main">
	<br>
	تفصیلی دوم : 
	<input type="text" name="sub">
	<br>
	<input type="submit" name="submit">
</form>
</body>	
