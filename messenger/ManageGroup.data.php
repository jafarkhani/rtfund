<?php
require_once '../header.inc.php';
require_once 'ManageGroup.class.php';
require_once 'definitions.inc.php';
require_once(inc_response);
require_once inc_dataReader;
require_once inc_PDODataAccess;

$task = isset ( $_POST ["task"] ) ? $_POST ["task"] : (isset ( $_GET ["task"] ) ? $_GET ["task"] : "");

switch ( $task) {
	

	case "SelectGrop":
		  SelectGrop();

	case "SaveGrp":
          SaveGrp();

	case "removeGrp":
		  removeGrp();
          
    case "SelectMembers":
          SelectMembers();

    case "SaveMember":
          SaveMember();

    case "removeMember":
          removeMember();
        
    case "SelectMyMessage" :
          SelectMyMessage();
        
    case "SelectMessageGrp" :
          SelectMessageGrp();
        
    case "SaveMsg" :
          SaveMsg();
    
    case "SearchMsg" :
          SearchMsg();
        
    case "DelMsg" :
          DelMsg();
        
    case "GetNotNumber" :
        GetNotNumber();
        
    case "SeenMsg" : 
        SeenMsg();
        
    case "GetInfo" : 
        GetInfo();
}

function SelectGrop()
{
    
	//.................. secure section .....................
	
	if (!empty($_REQUEST["fields"]) && !InputValidation::validate($_REQUEST["fields"], InputValidation::Pattern_EnAlphaNum, false)) {
		echo dataReader::getJsonData(array(), 0);
		die();
	}
	if (!empty($_REQUEST["query"]) && !InputValidation::validate($_REQUEST["query"], InputValidation::Pattern_FaEnAlphaNum, false)) {
		echo dataReader::getJsonData(array(), 0);
		die();
	}
	//.......................................................

	$where = " (1=1) ";
	$whereParam = array();

	$field = isset ( $_GET ["fields"] ) ? $_GET ["fields"] : "";

	if (isset ( $_GET ["query"] ) && $_GET ["query"] != "") {
			switch ( $field) {
				case "GroupTitle" :
					$where .= " AND GroupTitle LIKE :qry " ;
					$whereParam[":qry"] = "%" . $_GET["query"] . "%";

				break;
				case "GID" :
					$where .= " AND GID = :qry " ;
					$whereParam[":qry"] = $_GET["query"] ;

				break;
				
			}
		}

	$where .=  dataReader::makeOrder(); 

	$temp = manage_MSG_group::GetAll($where,$whereParam);
	$no = count($temp);
        //..........................secure section ........................
        $start = (int)$_GET["start"] ;
        $limit = (int)$_GET["limit"] ;
        if(!InputValidation::validate($_GET["callback"], InputValidation::Pattern_EnAlphaNum, false))
        {
            echo dataReader::getJsonData(array(), 0);
            die();
        }
        //................................................................
        $temp = array_slice($temp,$start,$limit);
	
	echo dataReader::getJsonData ($temp, $no, $_GET ["callback"] );
	die ();
}

function SaveGrp()
{
    
    //.................. secure section .....................
    if (!InputValidation::validate($_REQUEST['GID'], InputValidation::Pattern_Num, false)) {
            echo Response::createObjectiveResponse(false, ExceptionHandler::GetExceptionsToString());
            die();
    }
    
    $obj = new manage_MSG_group();
    PdoDataAccess::FillObjectByArray($obj, $_POST);
    
    if (empty($obj->GID)) {

        $size = $_FILES['FileType']['size'];

        if ($size > 2097152) { 
            echo '<script language="javascript">';
            echo 'alert("حجم فایل باید کمتراز 2 مگابایت باشد.")';
            echo '</script>';
            echo Response::createObjectiveResponse(false, "");
            die();
        }
        $st = preg_split("/\./", $_FILES ['FileType'] ['name']);

        $extension = $st [count($st) - 1];

        if (in_array($extension, array("jpg", "jpeg", "gif", "png", "pdf", "JPG", "JPEG", "GIF", "PNG", "PDF")) === false) {           
            echo '<script language="javascript">';
            echo 'alert("فرمت فایل غیرمجاز است.")';
            echo '</script>';
            echo Response::createObjectiveResponse(false, "");
            die();
        } else {
         
            $filetype = $obj->FileType = $extension; 
            $return = $obj->Add();    
               
            $filename = $obj->GID;

            if (file_exists(GRPPIC_DIRECTORY . $filename . "." . $filetype)) {
                unlink(GRPPIC_DIRECTORY . $filename . "." . $filetype);
            }
                
            $fp = fopen(GRPPIC_DIRECTORY . $filename . "." . $filetype, "w");
            fwrite($fp, fread(fopen($_FILES['FileType']['tmp_name'], 'r'), $_FILES['FileType']['size']));
            fclose($fp);
            
        }
    } 
    else {
        
        if(!empty($_FILES ['FileType'] ['name']))
        {
        $size = $_FILES['FileType']['size'];

        if ($size > 2097152) { 
            echo '<script language="javascript">';
            echo 'alert("حجم فایل باید کمتراز 2 مگابایت باشد.")';
            echo '</script>';
            echo Response::createObjectiveResponse(false, "");
            die();
        }
        $st = preg_split("/\./", $_FILES ['FileType'] ['name']);

        $extension = $st [count($st) - 1];

        if (in_array($extension, array("jpg", "jpeg", "gif", "png", "pdf", "JPG", "JPEG", "GIF", "PNG", "PDF")) === false) {           
            echo '<script language="javascript">';
            echo 'alert("فرمت فایل غیرمجاز است.")';
            echo '</script>';
            echo Response::createObjectiveResponse(false, "");
            die();
        } 
        
        $filetype = $obj->FileType = $extension; 
        $return = $obj->Edit();                   
        $filename = $obj->GID;

        if (file_exists(GRPPIC_DIRECTORY . $filename . "." . $filetype)) {
            unlink(GRPPIC_DIRECTORY . $filename . "." . $filetype);
        }

        $fp = fopen(GRPPIC_DIRECTORY . $filename . "." . $filetype, "w");
        fwrite($fp, fread(fopen($_FILES['FileType']['tmp_name'], 'r'), $_FILES['FileType']['size']));
        fclose($fp);
            
        
        } else {                     
            $return = $obj->Edit();                   
            $filename = $obj->GID;            
        }
        
    }
    
    if (!$return) {
        echo Response::createObjectiveResponse($return, ExceptionHandler::GetExceptionsToString());
        die();
    }
    echo Response::createObjectiveResponse(true, "");
    die();
    
    //......................
	/*$obj = new manage_MSG_group();
    PdoDataAccess::FillObjectByJsonData($obj, $_POST["record"]);
	
	if(empty($obj->GID))
		$return = $obj->Add();
	else
		$return = $obj->Edit();
	if(!$return)
	{
		echo Response::createObjectiveResponse($return, ExceptionHandler::GetExceptionsToString());
		die();
	}
	echo Response::createObjectiveResponse(true, "");
	die();*/
		
}

function removeGrp()
{
    //.................. secure section .....................
    if (!InputValidation::validate($_POST["GID"], InputValidation::Pattern_Num, false)) 
    {
            echo Response::createObjectiveResponse(false, ExceptionHandler::GetExceptionsToString());
            die();
    }
    //.......................................................

	$return = manage_MSG_group::Remove($_POST["GID"]);
	Response::createObjectiveResponse($return, (!$return ? ExceptionHandler::popExceptionDescription() : ""));
	die();
}

function SelectMembers()
{    
	//.................. secure section .....................
	
	if (!empty($_REQUEST["GID"]) && !InputValidation::validate($_REQUEST["GID"], InputValidation::Pattern_Num, false)) {
		echo dataReader::getJsonData(array(), 0);
		die();
	}
	
	//.......................................................

	$where = " GID =". $_GET['GID'];
	$where .=  dataReader::makeOrder();
    
    $temp = manage_MSG_members::GetAll($where);
  
	$no = count($temp);
        //..........................secure section ........................
        $start = (int)$_GET["start"] ;
        $limit = (int)$_GET["limit"] ;
        if(!InputValidation::validate($_GET["callback"], InputValidation::Pattern_EnAlphaNum, false))
        {
            echo dataReader::getJsonData(array(), 0);
            die();
        }
        //................................................................
        $temp = array_slice($temp,$start,$limit);

	echo dataReader::getJsonData ($temp, $no, $_GET ["callback"] );
	die ();
}


function SaveMember()
{ 
	$obj = new manage_MSG_members();   
   
    PdoDataAccess::FillObjectByJsonData($obj, $_POST["record"]);

	if(empty($obj->MID))
		$return = $obj->Add();
	else
		$return = $obj->Edit();
	if(!$return)
	{
		echo Response::createObjectiveResponse($return, ExceptionHandler::GetExceptionsToString());
		die();
	}
	echo Response::createObjectiveResponse(true, "");
	die();

}



function removeMember()
{
   
    //.................. secure section .....................
    if (!InputValidation::validate($_POST["MID"], InputValidation::Pattern_Num, false)) 
    {
            echo Response::createObjectiveResponse(false, ExceptionHandler::GetExceptionsToString());
            die();
    }
    if (!InputValidation::validate($_POST["GID"], InputValidation::Pattern_Num, false)) 
    {
            echo Response::createObjectiveResponse(false, ExceptionHandler::GetExceptionsToString());
            die();
    }
    //.......................................................

    $return = manage_MSG_members::Remove($_POST["GID"],$_POST["MID"]);
	Response::createObjectiveResponse($return, (!$return ? ExceptionHandler::popExceptionDescription() : ""));
	die();
}

function SelectMyMessage()
{    
    $where = "" ; 
    $whereParam = array(":PID" => $_SESSION["USER"]["PersonID"] );
    
	$temp = manage_MSG_messages::GetAllMyMessage($where,$whereParam);
  
	$no = count($temp);
        //..........................secure section ........................
        $start = (int)$_GET["start"] ;
        $limit = (int)$_GET["limit"] ;
        if(!InputValidation::validate($_GET["callback"], InputValidation::Pattern_EnAlphaNum, false))
        {
            echo dataReader::getJsonData(array(), 0);
            die();
        }
        //................................................................
        $temp = array_slice($temp,$start,$limit);
	
	echo dataReader::getJsonData ($temp, $no, $_GET ["callback"] );
	die ();
}

function SelectMessageGrp()
{           
    $where = "" ; 
    $whereParam = array(":GID" => $_REQUEST['GID']);
  
	$temp = manage_MSG_messages::GetAllGroupMessage($where,$whereParam);     
	$no = count($temp);
    
    //..........................secure section ........................
    $start = (int)$_GET["start"] ;
    $limit = (int)$_GET["limit"] ;
    if(!InputValidation::validate($_GET["callback"], InputValidation::Pattern_EnAlphaNum, false))
    {
        echo dataReader::getJsonData(array(), 0);
        die();
    }
    //................................................................
    $temp = array_slice($temp,$start,$limit);
	
	echo dataReader::getJsonData ($temp, $no, $_GET ["callback"] );
	die ();
}

function SearchMsg()
{                
    $where = "" ; 
    $whereParam = array(":PID" => $_SESSION["USER"]["PersonID"],":GID" => $_REQUEST['GID'],":STxt" => "%".$_REQUEST['SearchTxt']."%" );
    
	$temp = manage_MSG_messages::GetSearchMessage($where,$whereParam);

	$no = count($temp);
    //..........................secure section ........................
    $start = (int)$_GET["start"] ;
    $limit = (int)$_GET["limit"] ;
    if(!InputValidation::validate($_GET["callback"], InputValidation::Pattern_EnAlphaNum, false))
    {
        echo dataReader::getJsonData(array(), 0);
        die();
    }
    //................................................................
    $temp = array_slice($temp,$start,$limit);
	
	echo dataReader::getJsonData ($temp, $no, $_GET ["callback"] );
	die ();
}


function SaveMsg()
{
	//.................. secure section .....................
    if (!InputValidation::validate($_REQUEST['MsgTxt'], InputValidation::Pattern_FaEnAlphaNum, false)) {
        echo Response::createObjectiveResponse(false, ExceptionHandler::GetExceptionsToString());
        die();
    }
    if (!InputValidation::validate($_REQUEST['ParentMSGID'], InputValidation::Pattern_Num, false)) {
        echo Response::createObjectiveResponse(false, ExceptionHandler::GetExceptionsToString());
        die();
    }
    if (!InputValidation::validate($_REQUEST['GID'], InputValidation::Pattern_Num, false)) {
        echo Response::createObjectiveResponse(false, ExceptionHandler::GetExceptionsToString());
        die();
    }
    
    if (!InputValidation::validate($_REQUEST['MID'], InputValidation::Pattern_Num, false)) {
        echo Response::createObjectiveResponse(false, ExceptionHandler::GetExceptionsToString());
        die();
    }
        
    $obj = new manage_MSG_messages();    
    $obj->GID = $_REQUEST['GID'] ; 
    $obj->MID = $_REQUEST['MID'] ; 
    $obj->ParentMSGID = $_REQUEST['ParentMSGID'] ; 
    $obj->MSGID =  $_REQUEST['MSGID'] ; 
    $obj->message = (empty($_REQUEST['MsgTxt']) ? " " : $_REQUEST['MsgTxt'] ) ;
    $obj->SendingDate = PDONOW ;  
       
    if (empty($obj->MSGID) && !($obj->MSGID > 0) ) {
       
        $size = $_FILES['FileType']['size'];

        if ($size > 2097152) { 
            echo '<script language="javascript">';
            echo 'alert("حجم فایل باید کمتراز 2 مگابایت باشد.")';
            echo '</script>';
            echo Response::createObjectiveResponse(false, "");
            die();
        }
        $st = preg_split("/\./", $_FILES ['FileType'] ['name']);

        $extension = $st [count($st) - 1];

        if (!empty($_FILES ['FileType'] ['name']) && in_array($extension, array("jpg", "jpeg", "gif", "png", "pdf","txt","docx", "JPG", "JPEG", "GIF", "PNG", "PDF","TXT","DOCX")) === false) {           
            echo '<script language="javascript">';
            echo 'alert("فرمت فایل غیرمجاز است.")';
            echo '</script>';
            echo Response::createObjectiveResponse(false, "");
            die();
        } else {
         
            $filetype = $obj->FileType = $extension; 
            $return = $obj->Add();    
       
            $filename = $obj->MSGID;

            if(!empty($_FILES ['FileType'] ['name'])) 
            {
                if (file_exists(GRPMSGPIC_DIRECTORY . $filename . "." . $filetype)) {
                    unlink(GRPMSGPIC_DIRECTORY . $filename . "." . $filetype);
                }

                $fp = fopen(GRPMSGPIC_DIRECTORY . $filename . "." . $filetype, "w");
                fwrite($fp, fread(fopen($_FILES['FileType']['tmp_name'], 'r'), $_FILES['FileType']['size']));
                fclose($fp);
            }
                        
        }
    } 
    elseif(!empty($obj->MSGID)) 
    {
        
        $size = $_FILES['FileType']['size'];

        if ($size > 2097152) { 
            echo '<script language="javascript">';
            echo 'alert("حجم فایل باید کمتراز 2 مگابایت باشد.")';
            echo '</script>';
            echo Response::createObjectiveResponse(false, "");
            die();
        }
        $st = preg_split("/\./", $_FILES ['FileType'] ['name']);

        $extension = $st [count($st) - 1];

        if (!empty($_FILES ['FileType'] ['name']) && in_array($extension, array("jpg", "jpeg", "gif", "png", "pdf","txt","docx", "JPG", "JPEG", "GIF", "PNG", "PDF","TXT","DOCX")) === false) {           
            echo '<script language="javascript">';
            echo 'alert("فرمت فایل غیرمجاز است.")';
            echo '</script>';
            echo Response::createObjectiveResponse(false, "");
            die();
        } else {
         
            $filetype = $obj->FileType = $extension; 
            $return = $obj->EDIT();    
               
            $filename = $obj->MSGID;

            if(!empty($_FILES ['FileType'] ['name'])) 
            {
                if (file_exists(GRPMSGPIC_DIRECTORY . $filename . "." . $filetype)) {
                    unlink(GRPMSGPIC_DIRECTORY . $filename . "." . $filetype);
                }

                $fp = fopen(GRPMSGPIC_DIRECTORY . $filename . "." . $filetype, "w");
                fwrite($fp, fread(fopen($_FILES['FileType']['tmp_name'], 'r'), $_FILES['FileType']['size']));
                fclose($fp);
            }
                        
        }
    }
    
    if (!$return) {
        echo Response::createObjectiveResponse($return, ExceptionHandler::GetExceptionsToString());
        die();
    }
    echo Response::createObjectiveResponse(true, "");
    die();
		
}

function DelMsg()
{   
    
    //.................. secure section .....................
    if (!InputValidation::validate($_POST["MsgId"], InputValidation::Pattern_Num, false)) 
    {
            echo Response::createObjectiveResponse(false, ExceptionHandler::GetExceptionsToString());
            die();
    }
   
    //.......................................................

    $return = manage_MSG_messages::Remove($_POST["MsgId"]);
	Response::createObjectiveResponse($return, (!$return ? ExceptionHandler::popExceptionDescription() : ""));
	die();
}

function GetNotNumber() 
{
    
    //.................. secure section .....................
    if (!InputValidation::validate($_POST["GID"], InputValidation::Pattern_Num, false)) 
    {
            echo Response::createObjectiveResponse(false, ExceptionHandler::GetExceptionsToString());
            die();
    }   
    //.......................................................    
    $qry = " select me.GID , count(*) MsgNo
                        from MSG_messages me
                        inner join MSG_members mb 
                               on me.GID = mb.GID
                        left join MSG_messagestatus st
                               on me.MSGID = st.MSGID and mb.MID = st.MID
                     where mb.PersonID = :PID and me.GID = :GID and mb.MID <> me.MID and  st.MSID is null";
    $res = PdoDataAccess::runquery($qry, array(":PID" => $_SESSION["USER"]["PersonID"] , ":GID" => $_POST["GID"] ));
    
    echo Response::createObjectiveResponse(true,$res[0]['MsgNo']) ;
    die();
    
}

function SeenMsg() 
{    
    //.................. secure section .....................
    if (!InputValidation::validate($_POST["GID"], InputValidation::Pattern_Num, false)) 
    {
            echo Response::createObjectiveResponse(false, ExceptionHandler::GetExceptionsToString());
            die();
    }   
    //.......................................................    
    manage_MSG_messages::InsertSeenMsg("", array(":GID" => $_POST["GID"] )); 
    
    echo Response::createObjectiveResponse(true," ") ;
    die();
    
}


function GetInfo() 
{
    
    //.................. secure section .....................
    if (!InputValidation::validate($_POST["MSGID"], InputValidation::Pattern_Num, false)) 
    {
            echo Response::createObjectiveResponse(false, ExceptionHandler::GetExceptionsToString());
            die();
    }   
    
    //.......................................................    
    $qry = " SELECT ms.MSGID , ms.message message , concat(fname,' ', lname) FullName
             FROM MSG_messages ms
                            inner join MSG_members m
                                    on ms.GID = m.GID and ms.MID = m.MID
                            inner join BSC_persons pr using(PersonID)
                            left join MSG_messages pms 
                                    on ms.ParentMSGID = pms.MSGID                                    
                    where ms.MSGID = :MSGID
                    order by MSGID  ASC";
    $res = PdoDataAccess::runquery($qry, array(":MSGID" => $_POST["MSGID"] ));
    
    echo Response::createObjectiveResponse(true,"{MSGID:".$res[0]['MSGID']." ,FullName:".$res[0]['FullName'].",message:".$res[0]['message']."}" ) ;
    die();
    
}
	