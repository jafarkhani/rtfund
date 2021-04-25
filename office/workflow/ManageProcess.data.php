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
function GetTreeNodes()
{
	
	$nodes = PdoDataAccess::runquery("SELECT    wf1.StepRowID as id ,
												wf1.StepParentID as parentId ,
												wf1.StepDesc as text , 
												'true' as leaf,
												wf1.FlowID ,
												wf1.StepID as sid ,
												wf1.IsActive,
												wf1.IsOuter,
												wf1.JobID,
												wf1.PostID,
												wf1.PersonID,
												wf1.customer
												
												FROM WFM_flowsteps wf1 
															inner join WFM_flowsteps wf 
															    on  wf.StepID = 0 and wf.StepRowID = wf1.StepParentID
												
												where  wf1.FlowID = ". $_REQUEST['ParentID'] );
	
	$cur_level_uids = "";
    $returnArray = $nodes;
	$ref_cur_level_nodes = array(); 
	
	
	for($i=0; $i<count($nodes); $i++)
	{
            InputValidation::ArrayEncoding($returnArray[$i]);
            $ref_cur_level_nodes[] = & $returnArray[$i];
            $cur_level_uids .= $nodes[$i]["id"] . ",";
	}
  
	
	$cur_level_uids = substr($cur_level_uids, 0, strlen($cur_level_uids) - 1);
	  
        if(!InputValidation::validate($cur_level_uids, InputValidation::Pattern_NumComma,false)){
            echo Response::createObjectiveResponse(false, "خطا در داده ورودی");
            die();
        }
	
	while (true)
	{
		$nodes = PdoDataAccess::runquery("	SELECT  u.StepRowID as id,
													u.StepParentID as parentId,
													u.StepDesc as text, 
													'true' as leaf ,
													u.FlowID ,
													u.StepID as sid,
													p.StepDesc as ptext,
													u.IsActive as active ,
													u.IsOuter as iout,
													u.JobID as jid,
													u.PostID as poid,
													u.PersonID as pid,
													u.customer as cusid 

											FROM WFM_flowsteps u
													left join WFM_flowsteps p on u.StepParentID=p.StepRowID

											where u.StepParentID in
														   (" . $cur_level_uids . ")

											order by u.StepRowID");
	/*
		  echo PdoDataAccess::GetLatestQueryString();
	      print_r(ExceptionHandler::PopAllExceptions()); 
    */
		if(count($nodes) == 0)
			break;
		//............ add current level nodes to returnArray ................
		$temp_ref = array();
		$cur_level_uids = "";
		
		for($i=0; $i<count($nodes); $i++)
		{
			//............ extract current level pids ..................
			$cur_level_uids .= $nodes[$i]["id"] . ",";
			
			for($j=0; $j < count($ref_cur_level_nodes); $j++)
			{
				if($nodes[$i]["parentId"] == $ref_cur_level_nodes[$j]["id"])
				{
					if(!isset($ref_cur_level_nodes[$j]["children"]))
					{
						$ref_cur_level_nodes[$j]["children"] = array();
						$ref_cur_level_nodes[$j]["leaf"] = "false";
					}
					$ref_cur_level_nodes[$j]["children"][] = $nodes[$i];
					$temp_ref[] = & $ref_cur_level_nodes[$j]["children"][count($ref_cur_level_nodes[$j]["children"])-1];
					break;
				}
			}
		}
		
		$ref_cur_level_nodes = $temp_ref;
		$cur_level_uids = substr($cur_level_uids, 0, strlen($cur_level_uids) - 1);
                if(!InputValidation::validate($cur_level_uids, InputValidation::Pattern_NumComma,false)){
                    echo Response::createObjectiveResponse(false, "خطا در داده ورودی");
                    die();
                }
	}

	$str = json_encode($returnArray);

	$str = str_replace('"children"', 'children', $str);
	$str = str_replace('"leaf"', 'leaf', $str);
	$str = str_replace('"text"', 'text', $str);
	$str = str_replace('"id"', 'id', $str);
	$str = str_replace('"true"', 'true', $str);
	$str = str_replace('"false"', 'false', $str);
	$str = str_replace('"ptext"', 'ptext', $str);
	$str = str_replace('"active"', 'active', $str);
	$str = str_replace('"sid"', 'sid', $str);
	$str = str_replace('"iout"', 'iout', $str);
	$str = str_replace('"jid"', 'jid', $str);
	$str = str_replace('"poid"', 'poid', $str);
	$str = str_replace('"pid"', 'pid', $str);	
	$str = str_replace('"cusid"', 'cusid', $str);

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
