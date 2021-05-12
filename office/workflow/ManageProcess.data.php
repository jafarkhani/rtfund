<?php
//---------------------------
// programmer:	Mahdipour
// create Date:	400.01
//---------------------------
ini_set('display_errors', 'on');
require_once '../header.inc.php';
require_once inc_response;
require_once inc_dataReader;


$task = isset($_POST["task"]) ? $_POST["task"] : (isset($_GET["task"]) ? $_GET["task"] : "");

switch ($task)
{
	case "GetTreeNodes":
		GetTreeNodes();
		
	case "PostStore" :
		  PostStore() ;
	
	case "JobStore" :
		  JobStore() ;
	
	case "PersonStore" :
		  PersonStore() ;
		
	    
}

//-------------------------------------------------------------------

function GetTreeNodes() {
   
   $dt = PdoDataAccess::runquery("
		SELECT 
			StepParentID ParentID,StepRowID id,StepDesc as text,'true' as leaf, f.*
		FROM WFM_flowsteps f
		where FlowID=?
		order by StepParentID,StepDesc", array($_REQUEST['ParentID']));
  
    $returnArray = array();
    $refArray = array();

    foreach ($dt as $row) {
        if ($row["ParentID"] == 0) {
            $returnArray[] = $row;
            $refArray[$row["id"]] = &$returnArray[count($returnArray) - 1];
            continue;
        }

        $parentNode = &$refArray[$row["ParentID"]];

        if (!isset($parentNode["children"])) {
            $parentNode["children"] = array();
            $parentNode["leaf"] = "false";
        }
        $lastIndex = count($parentNode["children"]);
        $parentNode["children"][$lastIndex] = $row;
        $refArray[$row["id"]] = &$parentNode["children"][$lastIndex];
    }

    $str = json_encode($returnArray);

    $str = str_replace('"children"', 'children', $str);
    $str = str_replace('"leaf"', 'leaf', $str);
    $str = str_replace('"text"', 'text', $str);
    $str = str_replace('"id"', 'id', $str);
    $str = str_replace('"true"', 'true', $str);
    $str = str_replace('"false"', 'false', $str);

    echo $str;
    die();
}

function PostStore() {
	$where = ' where 1=1 ';
	$param = array();
        if (!empty($_GET["callback"]) && !InputValidation::validate($_GET["callback"], InputValidation::Pattern_EnAlphaNum, false)) {
		echo dataReader::getJsonData(array(), 0);
		die();
	}
	if (!InputValidation::validate($_REQUEST['query'], InputValidation::Pattern_FaEnAlphaNum, false)) {
		echo dataReader::getJsonData(array(), 0, $_GET["callback"]);
		die();
	}
	
	$dt = PdoDataAccess::runquery("select * from BSC_posts where IsActive='YES' " , array());
	echo dataReader::getJsonData($dt, count($dt), $_GET["callback"]);
	die();
}

function JobStore() {
	$where = ' where 1=1 ';
	$param = array();
        if (!empty($_GET["callback"]) && !InputValidation::validate($_GET["callback"], InputValidation::Pattern_EnAlphaNum, false)) {
		echo dataReader::getJsonData(array(), 0);
		die();
	}
	if (!InputValidation::validate($_REQUEST['query'], InputValidation::Pattern_FaEnAlphaNum, false)) {
		echo dataReader::getJsonData(array(), 0, $_GET["callback"]);
		die();
	}
	
	$dt = PdoDataAccess::runquery(" select JobID,concat(JobID,'-',PostName) title from BSC_jobs join BSC_posts using(PostID) " , array());
	echo dataReader::getJsonData($dt, count($dt), $_GET["callback"]);
	die();
}

?>
