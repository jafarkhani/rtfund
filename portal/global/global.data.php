<?php
//---------------------------
// programmer:	Jafarkhani
// create Date:	94.06
//---------------------------
require_once getenv("DOCUMENT_ROOT") . '/portal/header.inc.php';
require_once getenv("DOCUMENT_ROOT") . '/framework/person/persons.class.php';
require_once inc_response;
require_once inc_dataReader;

$task = isset($_POST["task"]) ? $_POST["task"] : (isset($_GET["task"]) ? $_GET["task"] : "");

switch ($task)
{
	case "SelectPersonInfo":
		SelectPersonInfo();
		
	case "SavePersonalInfo":
		SavePersonalInfo();
		
	case "AccDocFlow":
		AccDocFlow();
		
	case "CustomerLetters":
		CustomerLetters();
}

function SelectPersonInfo(){
	
	$temp = BSC_persons::SelectAll("PersonID=?", array($_SESSION["USER"]["PersonID"]));
	$temp = PdoDataAccess::fetchAll($temp, 0, 1);
	echo dataReader::getJsonData($temp, 1, $_GET["callback"]);
	die();
}

function SavePersonalInfo(){
	
	$obj = new BSC_persons();
	$obj->PersonID = $_SESSION["USER"]["PersonID"];
	PdoDataAccess::FillObjectByArray($obj, $_POST);
	
	$result = $obj->EditPerson();
	echo Response::createObjectiveResponse($result, "");
	die();
}

function AccDocFlow($CostID = null, $returnTotal = false){
	
	$CostID = $CostID== null ? $_REQUEST["CostID"] : $CostID;
	//$CurYear = substr(DateModules::shNow(),0,4);

	$temp = PdoDataAccess::runquery("select " .
			($returnTotal ? "sum(CreditorAmount-DebtorAmount) amount" : 
			" d.LocalNo,
			d.DocDate,
			d.description,
			di.DebtorAmount,
			di.CreditorAmount,
			di.details" ) . 
		" from ACC_DocItems di join ACC_docs d using(DocID)
		left join ACC_tafsilis t1 on(t1.TafsiliType=1 AND di.TafsiliID=t1.TafsiliID)
		left join ACC_tafsilis t2 on(t2.TafsiliType=1 AND di.TafsiliID2=t2.TafsiliID)
		where CostID=:cid AND (t1.ObjectID=:pid or t2.ObjectID=:pid)
			/*AND StatusID = ".ACC_STEPID_CONFIRM." */".
		($returnTotal ? " group by CostID " : "") .
		"order by DocDate
	", array(":pid" => $_SESSION["USER"]["PersonID"], ":cid" => $CostID));
	
	if($returnTotal)
		return $temp;
	
	$PreSum = 0;
	for($i=0; $i < $_GET["start"]*1 + $_GET["limit"]*1 && $i < count($temp); $i++)
	{
		$PreSum += $temp[$i]["CreditorAmount"] - $temp[$i]["DebtorAmount"];
		$temp[$i]["Remainder"] = $PreSum;		
	}
	
	$temp = array_slice($temp, $_GET["start"], $_GET["limit"]);
	
	echo dataReader::getJsonData($temp, count($temp), $_GET["callback"]);
	die();	
}

/*function CustomerLetters($returnMode = false){
	
	$list = PdoDataAccess::runquery("
		select * from OFC_letters l join OFC_LetterCustomers c using(LetterID)
		where c.IsHide='NO' AND l.AccessType=".OFC_ACCESSTYPE_NORMAL." 
			AND c.PersonID=" . $_SESSION["USER"]["PersonID"] ." ORDER BY LetterDate DESC");

	if($returnMode)
		return $list;
    echo dataReader::getJsonData($list, count($list), $_GET['callback']);
    die();
}*/
function CustomerLetters($returnMode = false){
    $where = " ";
    $param = array();
    if (isset($_GET["fields"]) && !empty($_GET["query"])) {

        $field = $_GET["fields"];
        $query = $_GET["query"];
        $field = $field == "LetterTitle" ? "c.LetterTitle" : $field;
        $query = $field == "LetterDate" ? DateModules::shamsi_to_miladi($query,"-") : $query;
        if ($field == "LetterDate"){
            $where .= " AND ".$field." = :f";
            $param[":f"] = $query;
        }else{
            $where .= " AND ".$field." like :f";
            $param[":f"] = "%" . $query . "%";
        }
    }
    $list = PdoDataAccess::runquery("
		select * from OFC_letters l join OFC_LetterCustomers c using(LetterID)
		where c.IsHide='NO' AND l.AccessType=".OFC_ACCESSTYPE_NORMAL." 
			AND c.PersonID = " . $_SESSION["USER"]["PersonID"] . " " . $where . " order by LetterDate DESC" , $param);

	if($returnMode)
		return $list;
    echo dataReader::getJsonData($list, count($list), $_GET['callback']);
    die();
}

?>
