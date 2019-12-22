<?php
//---------------------------
// programmer:	Mahdipour
// create Date:	91.06
//---------------------------
require_once '../../header.inc.php';
require_once 'sessions.class.php';
require_once(inc_response);
require_once inc_dataReader;

$task = isset ( $_POST ["task"] ) ? $_POST ["task"] : (isset ( $_GET ["task"] ) ? $_GET ["task"] : "");

	switch ( $task) {            
            
		case "Searchbank" :
                      Searchbank();
                    
                case "SaveBank" :
                      SaveBank() ;    
                    
                case "removebank" : 
                      removebank() ; 
                    
                    }

function Searchbank()
{             
        $where = dataReader::makeOrder();        
        $temp = manage_Session::GetAll($where);
        $no = count($temp);

        $temp = array_slice($temp, $_GET["start"], $_GET["limit"]);

        echo dataReader::getJsonData ( $temp, $no, $_GET ["callback"] );
        die ();		
}

function SaveBank()
{
		
	$obj = new manage_Session() ; 
	PdoDataAccess::FillObjectByJsonData($obj, $_POST["record"]);
	
	$st = preg_split('/:/',$obj->PersonID);
	$obj->PersonID = $st[0];
	
	$sit = preg_split('/:/',$obj->salary_item_type_id);
	$obj->salary_item_type_id = $sit[0];
	
	
	if($obj->SessionID)        
		$return = $obj->Edit();	
	else
		 $return = $obj->Add();	

	if($return)
	echo Response::createResponse(true,$obj->SessionID );
	else
		echo Response::createResponse(false , ExceptionHandler::ConvertExceptionsToJsObject());
	die();
            
}

function removebank()
{
	
	$return = manage_Session::Remove($_POST["bid"]);
	Response::createObjectiveResponse($return, (!$return ? ExceptionHandler::popExceptionDescription() : ""));
	die();
}

?>