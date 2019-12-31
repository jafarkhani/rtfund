<?php
/* 
 *  Programmer:	sh.Jafarkhani
 *  Create Date:	1389.10
 * 
 *  Modified : R.Mahdizadeh
    EnterReplacement added 
 *  Date : 1390-03-30
 * 
*/

define("PDONULL", "%pdonull%");
define("PDONOW", "now()");
/*define("PDONOW", "ADDTIME(now(), '01:30:00') ");*/

define("DEBUGQUERY", false);
require_once 'ExceptionHandler.class.php';
require_once 'DateModules.class.php';
require_once 'DataMember.class.php';
//require_once 'DataAudit.class.php';

class PdoDataAccess extends ExceptionHandler
{
	private static $DB;
	private static $ReportDB;
	private static $statements = array();
	private static $queryString = "";
	private static $executionTime = "";
	private static $defaultDB = "";

	function  __construct() {
		parent::__construct();
	}
	
	private static function CorrectFarsiString($query)
	{
		$ar_ya = "ي";
      	$fa_ya = "ی";
      	$ar_kaf = "ك";
      	$fa_kaf = "ک";
      	$trans = array($ar_ya => $fa_ya , $ar_kaf => $fa_kaf, "¬" => " ");
      	/*
		 * update ACC_blocks set BlockDesc = replace(blockDesc,'ي','ی')
		 * update ACC_blocks set BlockDesc = replace(blockDesc,'ك','ک')
		 */
      	return strtr($query, $trans);
	}
	
	public static function getPdoObject($_host = "", $_user = "", $_pass = "", $_default_db = "",$ShowException=false)
	{
		if($_host != "" && $_user != "" && $_pass != "" && $_default_db != "")
		{
			try{
				self::$defaultDB = $_default_db;
				return new PDO("mysql:host=" . $_host . ";dbname=" . $_default_db, $_user, $_pass,
						array(PDO::MYSQL_ATTR_INIT_COMMAND =>  "SET NAMES utf8"));
			}
	 	        catch (PDOException $e) 
		        {
					if ($ShowException)
							echo $e->getMessage().'<br>';
					echo " خطا در اتصال به بانک اطلاعاتی\n";
					die();			
			}
			return null;	
		}
		
		if(!isset(self::$DB))
		{
			try{
			    $_host = sys_config::$db_server['host'];
			    $_user = sys_config::$db_server['user'];
			    $_pass = sys_config::$db_server['pass'];
			    $_default_db = sys_config::$db_server['database'];
			    
				self::$defaultDB = $_default_db;
				
				self::$DB = new PDO("mysql:host=" . $_host . ";dbname=" . $_default_db, $_user, $_pass, 
					array(PDO::MYSQL_ATTR_INIT_COMMAND =>  "SET NAMES utf8"));
				
				return self::$DB;
		    }
		    catch (PDOException $e) 
		    {
				if ($ShowException)
						echo $e->getMessage().'<br>';
				echo " خطا در اتصال به بانک اطلاعاتی\n";
				die();		
			}
			return null;	
		}
		else 
			return self::$DB;
	}
	
	/**
	* متغیر هایی از شی داده مقصد که فاقد مقدار می باشند را با مقادیر مشابه در آرایه پر می کند
	 * @param object $obj
	 * @param array $record
	 * @return 
	 */
	public static function FillObjectByArray(&$obj, $record)
	{
		$keys = array_keys(get_object_vars($obj));

		for($i=0; $i < count($keys); $i++)
			if(isset($record[$keys[$i]]))
			{
				$record[$keys[$i]] = preg_replace('/\'/', "\\'", $record[$keys[$i]]);
				if($record[$keys[$i]] == "NULL")
					$obj->{ $keys[$i] } = null;
				else 
					$obj->{ $keys[$i] } = $record[$keys[$i]];
			}
		return true;
	}

	/**
	  * متغیر هایی از شی داده مقصد که فاقد مقدار می باشند را با مقادیر مشابه در مقدار json داده شده پر می کند
	 * @param object $sourceObj
	 * @param string $jsonData
	 * @return
	 */
	public static function FillObjectByJsonData($obj, $jsonData)
	{
		$st = stripslashes(stripslashes($jsonData));
		$data = json_decode($st);
		
		return self::FillObjectByObject($data, $obj);
	}

	/**
	  * متغیر هایی از شی داده مقصد که فاقد مقدار می باشند را با مقادیر مشابه در شی داده مبدا پر می کند
	 * @param object $sourceObj
	 * @param object $destinationObj
	 * @return 
	 */
	public static function FillObjectByObject($sourceObj, $destinationObj)
	{
		$src_keys = array_keys(get_object_vars($sourceObj));
		$dst_keys = array_keys(get_object_vars($destinationObj));
		
		for($i=0; $i < count($src_keys); $i++)
		{
			$index = array_search($src_keys[$i], $dst_keys);
			
			if(is_int($src_keys[$i]))
				continue;
			
			if($index !== false && !isset($destinationObj->{ $src_keys[$i] }))
				$destinationObj->{ $src_keys[$i] } = $sourceObj->{ $src_keys[$i] };
		}
		return true;
	}

	/**
	 * این تابع کوئری را اجرا کرده و اعضای داده ای شی که با ستون های کوئری همنام می باشند را با مقادیر رکورد اول پر می کند
	 * @param object $obj
	 * @param String $query
	 * @param 2D-array or 1D-array $whereParams
	 *
	 * @return boolean
	 */
	public static function FillObject(&$obj, $query, $whereParams = array(), $pdoObject = null)
	{
		$PDO_Obj = $pdoObject != null ? $pdoObject : self::getPdoObject();
		/*@var $PDO_Obj PDO*/
		//-------------------
		$mainQuery = $query;
		
		$statement = $PDO_Obj->prepare($query);
		
		$keys = array_keys($whereParams);
		for($i=0; $i < count($keys); $i++)
		{
			$st = (is_int($keys[$i])) ? $keys[$i] + 1 : $keys[$i];
			$whereParams[$keys[$i]] = self::CorrectFarsiString($whereParams[$keys[$i]]);
			$statement->bindParam($st, $whereParams[$keys[$i]]);
			
			if((is_int($keys[$i])))
				$mainQuery = preg_replace("/\?/", "'".$whereParams[$keys[$i]]."'", $mainQuery, 1);
			else
				$mainQuery = str_replace ($keys[$i], "'".$whereParams[$keys[$i]]."'", $mainQuery);
		}
		$statement->setFetchMode( PDO::FETCH_INTO, $obj);
		
		//.............................
		$startTime = microtime(true);
		$statement->execute();
		$endTime = microtime(true);
		self::$executionTime = $endTime - $startTime;
		self::$statements[$PDO_Obj->getAttribute(PDO::ATTR_CONNECTION_STATUS)] = $statement;
		self::$queryString = $mainQuery;
		self::LogQueryToDB();
		//.............................
		if($statement->errorCode() == "00000")
		{
			$obj = $statement->fetch( PDO::FETCH_INTO );
			$statement->closeCursor();
			return true;
		}
		
		parent::PushException($statement->errorInfo());
		return false;
	}

	
	public static function runquery($query, $whereParams = array(), $pdoObject = null)
	{
		$PDO_Obj = $pdoObject != null ? $pdoObject : self::getPdoObject();
		/*@var $PDO_Obj PDO*/
		//-------------------
		$mainQuery = $query;
		
		$statement = $PDO_Obj->prepare(self::CorrectFarsiString($query));
		
		if(!is_array($whereParams))
			$whereParams = array($whereParams);
		
		$keys = array_keys($whereParams);
		for($i=0; $i < count($keys); $i++)
		{
			$st = (is_int($keys[$i])) ? $keys[$i] + 1 : $keys[$i];
			$whereParams[$keys[$i]] = self::CorrectFarsiString($whereParams[$keys[$i]]);
			$statement->bindParam($st, $whereParams[$keys[$i]]);
			
			if((is_int($keys[$i])))
				$mainQuery = preg_replace("/\?/", "'".$whereParams[$keys[$i]]."'", $mainQuery, 1);
			else
				$mainQuery = str_replace ($keys[$i], "'".$whereParams[$keys[$i]]."'", $mainQuery);
		}
		//.............................
		$startTime = microtime(true);
		$statement->execute();
		$endTime = microtime(true);
		self::$executionTime = $endTime - $startTime;
		self::$statements[$PDO_Obj->getAttribute(PDO::ATTR_CONNECTION_STATUS)] = $statement;
		self::$queryString = $mainQuery;
		self::LogQueryToDB();
		//.............................	
		//.............................
		if($statement->errorCode() == "00000")
			return $statement->fetchAll();

		parent::PushException(array_merge($statement->errorInfo(), array("query" => $statement->queryString)));
		return false;
	} 
	
	/**
	 *
	 * @param string $query
	 * @param 2D-array or 1D-array $whereParams
	 * @return array or false on error
	 */
	public static function ReportServerRunquery($query, $whereParams = array(),$ShowException=false)
	{
		if(!isset(self::$ReportDB))
		{
			$_host = config::$db_servers['slave']['host'];
			$_user = config::$db_servers['slave']['report_user']; 
			$_pass = config::$db_servers['slave']['report_pass'];
			$_default_db = sys_config::$db_server['database'];

			self::$ReportDB = self::getPdoObject($_host,$_user,$_pass,$_default_db,$ShowException); 
		}
		$PDO_Obj = self::$ReportDB;
		/*@var $PDO_Obj PDO*/
		//-------------------
		$mainQuery = $query;
		
		$statement = $PDO_Obj->prepare(self::CorrectFarsiString($query));
		$keys = array_keys($whereParams);
		for($i=0; $i < count($keys); $i++)
		{
			$st = (is_int($keys[$i])) ? $keys[$i] + 1 : $keys[$i];
			$statement->bindParam($st, self::CorrectFarsiString($whereParams[$keys[$i]]));
			
			if((is_int($keys[$i])))
				$mainQuery = preg_replace("/\?/", "'".self::CorrectFarsiString($whereParams[$keys[$i]])."'", $mainQuery, 1);
			else
				$mainQuery = str_replace ($keys[$i], "'".self::CorrectFarsiString($whereParams[$keys[$i]])."'", $mainQuery);
		}
		//.............................
		$startTime = microtime(true);
		$statement->execute();
		$endTime = microtime(true);
		self::$executionTime = $endTime - $startTime;
		self::$statements[$PDO_Obj->getAttribute(PDO::ATTR_CONNECTION_STATUS)] = $statement;
		self::$queryString = $mainQuery;
		self::LogQueryToDB();
		//.............................	
		//.............................
		if($statement->errorCode() == "00000")
			return $statement->fetchAll();

		parent::PushException(array_merge($statement->errorInfo(), array("query" => $statement->queryString)));
		return false;
	} 
	
	public static function ReportServerRunquery_fetchMode($query, $whereParams = array())
	{
		if(!isset(self::$ReportDB))
		{
			$_host = config::$db_servers['slave']['host'];
			$_user = config::$db_servers['slave']['report_user']; 
			$_pass = config::$db_servers['slave']['report_pass'];
			$_default_db = sys_config::$db_server['database'];

			self::$ReportDB = self::getPdoObject($_host,$_user,$_pass,$_default_db,$ShowException); 
		}
		$PDO_Obj = self::$ReportDB;
		/*@var $PDO_Obj PDO*/
		//-------------------
		$mainQuery = $query;
		$statement = $PDO_Obj->prepare(self::CorrectFarsiString($query));
		$keys = array_keys($whereParams);
		for($i=0; $i < count($keys); $i++)
		{
			$st = (is_int($keys[$i])) ? $keys[$i] + 1 : $keys[$i];
			$whereParams[$keys[$i]] = self::CorrectFarsiString($whereParams[$keys[$i]]);
			$statement->bindParam($st, $whereParams[$keys[$i]]);
			
			if((is_int($keys[$i])))
				$mainQuery = preg_replace("/\?/", "'".$whereParams[$keys[$i]]."'", $mainQuery, 1);
			else
				$mainQuery = str_replace ($keys[$i], "'".$whereParams[$keys[$i]]."'", $mainQuery);
		}
		//.............................
		$statement->execute();
		self::$statements[$PDO_Obj->getAttribute(PDO::ATTR_CONNECTION_STATUS)] = $statement;
		self::$queryString = $mainQuery;
		//.............................
		if($statement->errorCode() == "00000")
			return $statement;

		parent::PushException(array_merge($statement->errorInfo(), array("query" => $statement->queryString)));
		return false;
	} 
	
	public static function runquery_fetchMode($query, $whereParams = array(), $pdoObject = null)
	{
		$PDO_Obj = $pdoObject != null ? $pdoObject : self::getPdoObject();
		/*@var $PDO_Obj PDO*/
		
		if(!is_array($whereParams))
			$whereParams = array($whereParams);
		
		//-------------------
		$mainQuery = $query;
		$statement = $PDO_Obj->prepare(self::CorrectFarsiString($query));
		$keys = array_keys($whereParams);
		for($i=0; $i < count($keys); $i++)
		{
			$st = (is_int($keys[$i])) ? $keys[$i] + 1 : $keys[$i];
			$whereParams[$keys[$i]] = self::CorrectFarsiString($whereParams[$keys[$i]]);
			$statement->bindParam($st, $whereParams[$keys[$i]]);
			
			if((is_int($keys[$i])))
				$mainQuery = preg_replace("/\?/", "'".$whereParams[$keys[$i]]."'", $mainQuery, 1);
			else
				$mainQuery = str_replace ($keys[$i], "'".$whereParams[$keys[$i]]."'", $mainQuery);
		}
		//.............................
		$startTime = microtime(true);
		$statement->execute();
		$endTime = microtime(true);
		self::$executionTime = $endTime - $startTime;
		self::$statements[$PDO_Obj->getAttribute(PDO::ATTR_CONNECTION_STATUS)] = $statement;
		self::$queryString = $mainQuery;
		self::LogQueryToDB();
		//.............................
		//.............................
		if($statement->errorCode() == "00000")
			return $statement;

		parent::PushException(array_merge($statement->errorInfo(), array("query" => $statement->queryString)));
		return false;
	} 
	
	public static function fetchAll($statement, $start, $limit)
	{
		$temp = array();
		
		$index = 0;
		while($index < $start*1)
		{
			if(!$statement->fetch(PDO::FETCH_ASSOC))
				return $temp;
			$index++;
		}
		
		while($index < $start*1 + $limit*1)
		{
			$row = $statement->fetch(PDO::FETCH_ASSOC);
			if(!$row)
				return $temp;
			$temp[] = $row;
			$index++;
		}		
		return $temp;
				
		/*$row = $statement->fetch(PDO::FETCH_ASSOC, PDO::FETCH_ORI_ABS, (int)$start);
		$temp[] = $row;
		$row = $statement->fetch(PDO::FETCH_ASSOC, PDO::FETCH_ORI_ABS, 5);
		
		$start = 1;
		while((int)$limit - (int)$start > 0)
		{
			$record = $statement->fetch(PDO::FETCH_ASSOC);
			if(!$record)
				return $temp;
			$temp[] = $record;
			$start++;
		}
		return $temp;*/
	}

	/**
	 *
	 * @param string $tableName
	 * @param Object $obj
	 * @return boolean
	 */
	public static function insert($tableName, $obj, $pdoObject = null)
	{	
		$PDO_Obj = $pdoObject != null ? $pdoObject : self::getPdoObject();
		/*@var $PDO_Obj PDO*/
		//-------------------

		$Arr = self::GetObjectMembers($obj, "insert");
		if($Arr === false)
		{
			ExceptionHandler::PushException("خطا در داده های ورودی");
			return false;
		}
		$KeyArr = array_keys($Arr);
		//.................................................		
		$flds = "";
		$values = "";
		for($i=0; $i < count($KeyArr); $i++)
		{
			$st = $Arr[$KeyArr[$i]];

			if($st === PDONULL || $st === "")
				$st = null;
			else if($st === PDONOW)
			{
				$flds .= $KeyArr[$i] . ",";
				$values .= PDONOW . ",";
			}else
			{
				$flds .= $KeyArr[$i] . ",";
				$values .= ":fld" . ($i<10 ? "0" : "") . $i . ",";
			}
		}
		$flds = substr($flds, 0, strlen($flds) - 1);
		$values = substr($values, 0, strlen($values) - 1);

		//.................................................
		$mainQuery = "insert into " . $tableName . "(" . $flds . ") values (" . $values . ")";
		
		$statement = $PDO_Obj->prepare("insert into " . $tableName . "(" . $flds . ") values (" . $values . ")");
		
		for($i=0; $i < count($KeyArr); $i++)
		{
			$Arr[$KeyArr[$i]] = self::CorrectFarsiString($Arr[$KeyArr[$i]]);
			if($Arr[$KeyArr[$i]] !== PDONULL && $Arr[$KeyArr[$i]] !== "" && $Arr[$KeyArr[$i]] !== PDONOW)
			{
				$statement->bindValue(":fld" . ($i<10 ? "0" : "") . $i, $Arr[$KeyArr[$i]]);
				$mainQuery = str_replace(":fld" . ($i<10 ? "0" : "") . $i, "'".$Arr[$KeyArr[$i]]."'", $mainQuery);
			}
		}               
                      
		//.............................
		$startTime = microtime(true);
		$statement->execute();
		$endTime = microtime(true);
		self::$executionTime = $endTime - $startTime;
		self::$statements[$PDO_Obj->getAttribute(PDO::ATTR_CONNECTION_STATUS)] = $statement;
		self::$queryString = $mainQuery;
		self::LogQueryToDB();
		//.............................
		if($statement->errorCode() == "00000")
			return true;

		parent::PushException($statement->errorInfo());
		return false;
	}

	/**
	 *
	 * @return integer or null on error
	 */
	public static function InsertID($pdoObject = null)
	{
		$PDO_Obj = $pdoObject != null ? $pdoObject : self::getPdoObject();
		/*@var $PDO_Obj PDO*/
		//-------------------
		
		return $PDO_Obj->lastInsertId();
		
		return null;
	}

	/**
	 *
	 * @param string $tableName
	 * @param Object $obj
	 * @param string $where
	 * @param 2D-array or 1D-array $whereParams
	 * @return boolean
	 */
	public static function update($tableName, $obj, $where = "", $whereParams = array(), $pdoObject = null)
	{
       
		$PDO_Obj = $pdoObject != null ? $pdoObject : self::getPdoObject();
		/*@var $PDO_Obj PDO*/
		//-------------------
		
		$Arr = self::GetObjectMembers($obj, "update");
		if($Arr === false)
		{
			ExceptionHandler::PushException("خطا در داده های ورودی");
			return false;
		}
		$KeyArr = array_keys($Arr);


		$flds = "";
		for($i=0; $i < count($KeyArr); $i++)
		{
			$st = $Arr[$KeyArr[$i]];
			if($st === PDONULL || $st === "")
			{
				$flds .= $KeyArr[$i] . "=null,";
			}
			else if($st === PDONOW)
			{
				$flds .= $KeyArr[$i] . "=" . PDONOW . ",";
			}
			else 
			{
				$flds .= $KeyArr[$i] . "=:fld" . ($i<10 ? "0" : "") . $i . ",";
			}
		}
		$flds = substr($flds, 0, strlen($flds) - 1);

		$where = ($where != "") ? " where " . $where : "";
		$mainQuery = "update " . $tableName . " set " . $flds . $where;
		$statement = $PDO_Obj->prepare("update " . $tableName . " set " . $flds . $where);

		$keys = array_keys($whereParams);
		for($i=0; $i < count($keys); $i++)
		{
			$whereParams[$keys[$i]] = self::CorrectFarsiString($whereParams[$keys[$i]]);
			$st = (is_int($keys[$i])) ? $keys[$i] + 1 : $keys[$i];
			$statement->bindParam($st, $whereParams[$keys[$i]]);
			
			if((is_int($keys[$i])))
				$mainQuery = preg_replace("/\?/", "'".$whereParams[$keys[$i]]."'", $mainQuery, 1);
			else
				$mainQuery = str_replace ($keys[$i], "'".$whereParams[$keys[$i]]."'", $mainQuery);
		}
		
		for($i=0; $i < count($KeyArr); $i++)
		{
			$Arr[$KeyArr[$i]] = self::CorrectFarsiString($Arr[$KeyArr[$i]]);
			if($Arr[$KeyArr[$i]] !== PDONULL && $Arr[$KeyArr[$i]] !== "" && $Arr[$KeyArr[$i]] !== PDONOW)
			{
				$statement->bindParam(":fld" . ($i<10 ? "0" : "") . $i, $Arr[$KeyArr[$i]]);
				$mainQuery = str_replace(":fld" . ($i<10 ? "0" : "") . $i, "'".$Arr[$KeyArr[$i]]."'", $mainQuery);
			}
		}

		//.............................
		$startTime = microtime(true);
		$statement->execute();
		$endTime = microtime(true);
		self::$executionTime = $endTime - $startTime;
		self::$statements[$PDO_Obj->getAttribute(PDO::ATTR_CONNECTION_STATUS)] = $statement;
		self::$queryString = $mainQuery;
		self::LogQueryToDB();
		//.............................
		if($statement->errorCode() == "00000")
			return true;

		parent::PushException($statement->errorInfo());
		return false;
	}

	/**
	 *
	 * @return integer or null on error
	 */
	public static function AffectedRows($pdoObject = null)
	{
		$PDO_Obj = $pdoObject != null ? $pdoObject : self::getPdoObject();
		$statement = self::$statements[$PDO_Obj->getAttribute(PDO::ATTR_CONNECTION_STATUS)];
		if(isset($statement))
			return $statement->rowCount();
		
		return null;
	}
	
	/**
	 * یک رکورد را درج می کند و اگر رکورد وجود داشته باشد آن را به روز می کند
	 *
	 * @param string $tableName
	 * @param Object $obj
	 * @return boolean
	 */
	public static function replace($tableName, $obj, $pdoObject = null)
	{	
		$PDO_Obj = $pdoObject != null ? $pdoObject : self::getPdoObject();
		
		$Arr = self::GetObjectMembers($obj, "");
		$KeyArr = array_keys($Arr);
		//.................................................		
		$flds = "";
		$flds2 = "";
		$values = "";
		for($i=0; $i < count($KeyArr); $i++)
		{
			$st = $Arr[$KeyArr[$i]];
			$flds .= $KeyArr[$i] . ",";
			$values .= ":fld" . $i . ",";				
						
			if($st === PDONULL || $st === "")
			{
				$flds2 .= $KeyArr[$i] . "=null,";
			}
			else if($st === PDONOW)
			{
				$flds2 .= $KeyArr[$i] . "=" . PDONOW . ",";
			}
			else
			{
				$flds2 .= $KeyArr[$i] . "=:fld" . $i . ",";
			}
		}
		$flds = substr($flds, 0, strlen($flds) - 1);
		$flds2 = substr($flds2, 0, strlen($flds2) - 1);
		$values = substr($values, 0, strlen($values) - 1);
		//.................................................
		$query = "insert into " . $tableName . "(" . $flds . ") values (" . $values . ")
			ON DUPLICATE KEY UPDATE " . $flds2;
		$mainQuery = $query;
		
		$statement = $PDO_Obj->prepare($query);
		
		for($i=0; $i < count($KeyArr); $i++)
		{
			$st = $Arr[$KeyArr[$i]];
			$statement->bindValue(":fld" . $i, self::CorrectFarsiString($st));
			
			$mainQuery = str_replace(":fld" . $i, "'".self::CorrectFarsiString($st)."'", $mainQuery);
		}
		
		//.............................
		$startTime = microtime(true);
		$statement->execute();
		$endTime = microtime(true);
		self::$executionTime = $endTime - $startTime;
		self::$statements[$PDO_Obj->getAttribute(PDO::ATTR_CONNECTION_STATUS)] = $statement;
		self::$queryString = $mainQuery;
		self::LogQueryToDB();
		//.............................
		if($statement->errorCode() == "00000")
			return true;

		parent::PushException($statement->errorInfo());
		return false;
	}

	/**
	 *
	 * @param string $tableName
	 * @param string $where
	 * @param 2D-array or 1D-array $whereParams
	 * @return boolean
	 */
	public static function delete($tableName, $where = "", $whereParams = array(), $pdoObject = null)
	{
		$PDO_Obj = $pdoObject != null ? $pdoObject : self::getPdoObject();
			
		$where = ($where != "") ? " where " . $where : "";
		$statement = $PDO_Obj->prepare("delete from " . $tableName . $where);
		$mainQuery = "delete from " . $tableName . $where;
		
		$keys = array_keys($whereParams);
		for($i=0; $i < count($keys); $i++)
		{
			$whereParams[$keys[$i]] = self::CorrectFarsiString($whereParams[$keys[$i]]);
			$st = (is_int($keys[$i])) ? $keys[$i] + 1 : $keys[$i];
			$statement->bindParam($st, $whereParams[$keys[$i]]);
			
			if((is_int($keys[$i])))
				$mainQuery = preg_replace("/\?/", "'".self::CorrectFarsiString($whereParams[$keys[$i]])."'", $mainQuery, 1);
			else
				$mainQuery = str_replace ($keys[$i], "'".self::CorrectFarsiString($whereParams[$keys[$i]])."'", $mainQuery);
		}

		//.............................
		$startTime = microtime(true);
		$statement->execute();
		$endTime = microtime(true);
		self::$executionTime = $endTime - $startTime;
		self::$statements[$PDO_Obj->getAttribute(PDO::ATTR_CONNECTION_STATUS)] = $statement;
		self::$queryString = $mainQuery;
		self::LogQueryToDB();
		//.............................
		if($statement->errorCode() == "00000")
			return true;

		parent::PushException($statement->errorInfo());
		return false;
	}
	
	/**
	 * @param $tableName : name of db table
	 * @param $field : the column that you want the max
	 * @param string $where
	 * @param 2D-array or 1D-array $whereParams
	 * @return int : max field value of table , if table is empty return 0
	 */
	public static function GetLastID($tableName, $field, $where = "", $whereParams = array(), $pdoObject = null)
	{
		$PDO_Obj = $pdoObject != null ? $pdoObject : self::getPdoObject();
			
		$where = ($where != "") ? " where " . $where : "";
		$statement = $PDO_Obj->prepare("select ifnull(max($field),0) as id from $tableName" . $where);
		$mainQuery = "select ifnull(max($field),0) as id from $tableName" . $where;
		
		$keys = array_keys($whereParams);
		for($i=0; $i < count($keys); $i++)
		{
			$st = (is_int($keys[$i])) ? $keys[$i] + 1 : $keys[$i];
			$whereParams[$keys[$i]] = self::CorrectFarsiString($whereParams[$keys[$i]]);
			$statement->bindParam($st, $whereParams[$keys[$i]]);
			
			if((is_int($keys[$i])))
				$mainQuery = preg_replace("/\?/", "'".self::CorrectFarsiString($whereParams[$keys[$i]])."'", $mainQuery, 1);
			else
				$mainQuery = str_replace ($keys[$i], "'".self::CorrectFarsiString($whereParams[$keys[$i]])."'", $mainQuery);
		}
		
		$statement->execute();
		//.............................
		self::$statements[$PDO_Obj->getAttribute(PDO::ATTR_CONNECTION_STATUS)] = $statement;
		//.............................
		self::$queryString = $mainQuery;
		//.............................
		if($statement->errorCode() == "00000")
		{
			$dt = $statement->fetchAll();
			return (count($dt) != 0) ? $dt[0][0] : 0;
		}

		parent::PushException($statement->errorInfo());
		return false;
	}

	public static function RecordExist($tableName, $obj)
	{
		$PDO_Obj = self::getPdoObject();
		/*@var $PDO_Obj PDO*/
		//-------------------

		$Arr = self::GetObjectMembers($obj, "update");
		if($Arr === false)
			return false;
		$KeyArr = array_keys($Arr);

		$where = "1=1";
		for($i=0; $i < count($KeyArr); $i++)
		{
			$st = $Arr[$KeyArr[$i]];
			if($st === PDONULL || $st === "")
			{
				$where .= " AND " . $KeyArr[$i] . " is null";
			}
			else if($st === PDONOW)
			{
				$where .= " AND " . $KeyArr[$i] . "=" . PDONOW;
			}
			else 
			{
				$where .= " AND " . $KeyArr[$i] . "=:fld" . ($i<10 ? "0" . $i : $i);
			}
		}

		$mainQuery = "select * from " . $tableName . " where " . $where;
		$statement = $PDO_Obj->prepare($mainQuery);

		for($i=0; $i < count($KeyArr); $i++)
		{
			$st = $Arr[$KeyArr[$i]];
			if($st !== PDONULL && $st !== "" && $st !== PDONOW)
			{
				$statement->bindParam(":fld" . ($i<10 ? "0" . $i : $i), self::CorrectFarsiString($st));
				$mainQuery = str_replace(":fld" . ($i<10 ? "0" . $i : $i), "'".self::CorrectFarsiString($st)."'", $mainQuery);
			}
		}

		//.............................
		$startTime = microtime(true);
		$statement->execute();
		$endTime = microtime(true);
		self::$executionTime = $endTime - $startTime;
		self::$statements[$PDO_Obj->getAttribute(PDO::ATTR_CONNECTION_STATUS)] = $statement;
		self::$queryString = $mainQuery;
		self::LogQueryToDB();
		//.............................
		if($statement->errorCode() == "00000")
			return $statement->rowCount() != 0;

		parent::PushException($statement->errorInfo());
		return false;
	}
	
	/**
	 * آخرین کوئری اجرا شده را برمی گرداند.
	 */
	public static function GetLatestQueryString()
	{
		if(self::$queryString != "")
			return self::$queryString;
	}

	public static function SecureAudit($task)
	{   
	  return;
	  /*
                $mysql = pdodb::getInstance();        
		$query = "insert into SysAudit (UserID, ActionDesc, IPAddress, SysCode) " .
			"values(?, ?, ?, ?) ";
        
		$WhereParams = array();
                array_push($WhereParams, $_SESSION['UserID']); 
                array_push($WhereParams, $task); 
                array_push($WhereParams, $_SESSION['LIPAddress']); 
                array_push($WhereParams, $_SESSION['SystemCode']); 
                
                $mysql->Prepare($query);
                $mysql->ExecuteStatement($WhereParams);
          */
	}
	
	/**
	 *
	 * @param string $task
	 * @return boolean
	 */
	public static function audit($task, $pdoObject = null)
	{  return;
		$PDO_Obj = $pdoObject != null ? $pdoObject : self::getPdoObject();
		
		$query = "insert into SysAudit (UserID, ActionDesc, IPAddress, SysCode) " .
			"values('" . $_SESSION['UserID'] . "', '$task', '" . $_SESSION['LIPAddress'] . "', 
			'" . $_SESSION['SystemCode'] . "') ";
		
		$statement = $PDO_Obj->prepare($query);
		$statement->execute();
		//.............................
		self::$statements[$PDO_Obj->getAttribute(PDO::ATTR_CONNECTION_STATUS)] = $statement;
		//.............................
		if($statement->errorCode() == "00000")
      		return true;
		
		parent::PushException($statement->errorInfo());
		return false;
	}
	
	//------------ photo in db operation ------------------
	/**
	 *
	 * @param string $query example : insert into PersonSigns values(:p, :photo)
	 * @param array $photoParams	: array(":p" => $PersonID)
	 * @param array $whereParams	: array(":photo" => $PhotoContent)
	 * @return boolean 
	 */
	public static function runquery_photo($query, $photoParams = array() , $whereParams = array(), $pdoObject = null)
	{
		
		$PDO_Obj = $pdoObject != null ? $pdoObject : self::getPdoObject();
		/*@var $PDO_Obj PDO*/
		//-------------------
		$mainQuery = $query;
		
		$statement = $PDO_Obj->prepare(self::CorrectFarsiString($query));
		
		if(!is_array($whereParams))
			$whereParams = array($whereParams);
		
		$index = 1;
		
		$keys = array_keys($photoParams);
		for($i=0; $i < count($keys); $i++)
		{
			$st = (is_int($keys[$i])) ? $index++ : $keys[$i];
			$statement->bindParam($st, $photoParams[$keys[$i]], PDO::PARAM_LOB);
		}
		
		$keys = array_keys($whereParams);
		for($i=0; $i < count($keys); $i++)
		{
			$st = (is_int($keys[$i])) ? $index++ : $keys[$i];
			$whereParams[$keys[$i]] = self::CorrectFarsiString($whereParams[$keys[$i]]);
			$statement->bindParam($st, $whereParams[$keys[$i]]);
			if((is_int($keys[$i])))
				$mainQuery = preg_replace("/\?/", "'".$whereParams[$keys[$i]]."'", $mainQuery, 1);
			else
				$mainQuery = str_replace ($keys[$i], "'".$whereParams[$keys[$i]]."'", $mainQuery);
		}
		
		//.............................
		$startTime = microtime(true);
		$statement->execute();
		$endTime = microtime(true);
		self::$executionTime = $endTime - $startTime;
		self::$statements[$PDO_Obj->getAttribute(PDO::ATTR_CONNECTION_STATUS)] = $statement;
		self::$queryString = $mainQuery;
		self::LogQueryToDB(true);
		//.............................	
		
		if($statement->errorCode() == "00000")
			return $statement->fetchAll();

		parent::PushException(array_merge($statement->errorInfo(), array("query" => $statement->queryString)));
		return false;
	}
	
	//-----------------------------------------------------

	private static function LogQueryToDB()
	{
		return;
		$PDO_Obj = self::getPdoObject();
		$statement = self::$statements[$PDO_Obj->getAttribute(PDO::ATTR_CONNECTION_STATUS)];
		
		if(DEBUGQUERY === false)
			return;
		
		if($statement->errorCode() == "00000" || $statement->errorCode() == "HY000")
			return;
		
		$db = self::$defaultDB;
		$pdo = new PDO("mysql:host=" . config::$db_servers['master']['host'] . ";dbname=" . 
				config::$db_servers['master']['dataanalysis_db'], 
				config::$db_servers['master']['dataanalysis_user'], 
				config::$db_servers['master']['dataanalysis_pass'], 
				array(PDO::MYSQL_ATTR_INIT_COMMAND =>  "SET NAMES utf8"));
				
		$query = "insert into SystemDBLog (page,query,SerializedParam,UserID,IPAddress,SysCode,ExecuteTime,QueryStatus,DBName)
				values (:page,:query,:SerializedParam,:UserID,:IPAddress,:SysCode,:ExecuteTime,:QueryStatus,:DBName)";
		
		$stm = $pdo->prepare($query);
		$whereParam = array(
			':page' => $_SERVER['SERVER_NAME'].$_SERVER['PHP_SELF'],
			":query" => self::$queryString . ($statement->errorCode() == "00000" ? "" : "\n\n" . implode(",", $statement->errorInfo())),
			":SerializedParam" => "",
			':UserID' => isset($_SESSION['UserID']) ? $_SESSION['UserID'] : "",
			':IPAddress' => $_SESSION['LIPAddress'],
			':SysCode' => $_SESSION['SystemCode'],
			':ExecuteTime' => self::$executionTime,
			':QueryStatus' => $statement->errorCode() == "00000" ? "SUCCESS" : "FAILED",
			':DBName' => $db);
		$keys = array_keys($whereParam);
		for($i=0; $i < count($keys); $i++)
		{
			$st = (is_int($keys[$i])) ? $keys[$i] + 1 : $keys[$i];
			$stm->bindParam($keys[$i], $whereParam[$keys[$i]]);
		}
		
		$stm->execute();
		
		return true;
	}
	
	private static function GetObjectMembers($obj, $action)
	{
		$obj = (array) $obj;
		
		$KeyArr = array_keys($obj);
		$valueArr = array();
		for($i=0; $i<count($KeyArr); $i++)
		{
			$origName = $KeyArr[$i];

			if(is_array($obj[$origName]))
				continue;
			if($origName == "exceptions")
				continue;
			if($origName[0] == "_")
				continue;
			if(strlen($origName) > 3 && substr($origName, 0, 3) == "DT_")
				continue;

			$KeyArr[$i] = str_replace(chr(0), "&", $KeyArr[$i]);
			$temp = preg_split('/&/', $KeyArr[$i]);
			$KeyArr[$i] = $temp[count($temp)-1];

			$st = self::DataMemberValidation($action, $obj, $KeyArr[$i], $obj[$origName]);
			if($st === false)
				return false;

			if(isset($st))
				$valueArr[$KeyArr[$i]] = $st;
			/*
			if($action == "insert")
			{
				$st = self::DataMemberValidation($obj, $KeyArr[$i], $obj[$origName]);
				if($st === false)
					return false;

				if(isset($obj[$origName]))
					$valueArr[$KeyArr[$i]] = $st;
			}
			else if(isset($obj[$origName]))
			{
				$st = self::DataMemberValidation($obj, $KeyArr[$i], $obj[$origName]);
				if($st === false)
					return false;
				$valueArr[$KeyArr[$i]] = $obj[$origName];
			}*/
		}
		return $valueArr;
	}

	private static function DataMemberValidation($action, $obj, $key, $value)
	{
		$checkDT = (isset($obj['DT_' . $key])) ? $obj['DT_' . $key] : null;
		
		if($checkDT === null)
			return $value;
		//...................................
		if($action == "insert" && DataMember::GetNotNullValue($checkDT) && ($value == PDONULL || $value == ""))
		{
			ExceptionHandler::PushException("فیلد " . $key . " نمی تواند مقدار null داشته باشد.");
			return false;
		}
		//...................................
		$defaultValue = DataMember::GetDefaultValue($checkDT);
		if(empty($value))
		{
			if(isset($defaultValue))
				$value = $defaultValue;
			else
				return $value;
		}
		//...................................
		switch ($checkDT["DataType"])
		{
			case DataMember::DT_DATE :
				if($value != PDONOW)
					$value = DateModules::shamsi_to_miladi($value);
				break;
			case DataMember::DT_TIME :
				if(strlen($value) > 8)
					$value = substr($value, strlen($value)-8);
		}
		//...................................
		if(!DataMember::IsValid($checkDT, $value))
			return false;
		//...................................
		return $value;
	}
        
	public static function EnterReplacement($string)
	{
          $trans = array("\n"=>".","\r"=>".","\n\r"=>".","\r\n"=>".");
          $string = strtr($string,$trans);
          return $string;

        }

}
/**
 * const TableName = "";
 * const TableKey = ""; 
 */
abstract class OperationClass extends PdoDataAccess {

    const ERR_Add = 'خطا در ذخیره اطلاعات';
    const ERR_Edit = 'خطا در ویرایش اطلاعات';
    const ERR_Remove = 'خطا در حذف اطلاعات';
    const UsedTplItem = 'آیتم مورد نظر استفاده شده است و قابل حذف نمی باشد.';

    function __construct($id = '') {
        if ($id != '') {
            parent::FillObject($this, "select * from " . static::TableName . " where " . static::TableKey . " =:id", array(":id" => $id));
        }
    }

	public static function LastID($pdo = null) {
		return PdoDataAccess::GetLastID(static::TableName, static::TableKey, "", array(), $pdo)+1;
	}
	
	public function SaveTrigger($pdo = null){
		return true;
	}

	public function Add($pdo = null) {

		if(!$this->SaveTrigger($pdo))
			return false;
		
        if (!parent::insert(static::TableName, $this, $pdo))
		{
			ExceptionHandler::PushException(self::ERR_Add);
			return false;
		} 

        $this->{static::TableKey} = parent::InsertID($pdo);
		
        $daObj = new DataAudit();
        $daObj->ActionType = DataAudit::Action_add;
        $daObj->MainObjectID = $this->{static::TableKey};
        $daObj->TableName = static::TableName;
        $daObj->execute();

        return true;
    }

    public function Edit($pdo = null) {
		
		if(!$this->SaveTrigger($pdo))
			return false;
		
        if (parent::update(static::TableName, $this, static::TableKey . 
			" =:id ", array(":id" => $this->{static::TableKey}), $pdo) === false) 
		{
			ExceptionHandler::PushException(self::ERR_Edit);
			return false;
		}

        $daObj = new DataAudit();
        $daObj->ActionType = DataAudit::Action_update;
        $daObj->MainObjectID = $this->{static::TableKey};
        $daObj->TableName = static::TableName;
        $daObj->execute();

        return true;
    }

	public function ReplaceRecord($pdo = null) {

        if (!parent::replace(static::TableName, $this, $pdo))
		{
			ExceptionHandler::PushException(self::ERR_Add);
			return false;
		}

        $daObj = new DataAudit();
        $daObj->ActionType = DataAudit::Action_replace;
        $daObj->MainObjectID = $this->{static::TableKey};
        $daObj->TableName = static::TableName;
        $daObj->execute();

        return true;
    }
    
	public function BeforeRemoveTrigger($pdo = null){
		return true;
	}

    public function Remove($pdo = null) {
		
		if(!$this->BeforeRemoveTrigger($pdo))
			return false;
		
        if (!parent::delete(static::TableName, 
				static::TableKey . "=:id", array(":id" => $this->{static::TableKey}), $pdo))
        {
			ExceptionHandler::PushException(self::ERR_Remove);
			return false;
		}
		
        $daObj = new DataAudit();
        $daObj->ActionType = DataAudit::Action_delete;
        $daObj->MainObjectID = $this->{static::TableKey};
        $daObj->TableName = static::TableName;
        $daObj->execute();

        return true;
    }

    public static function Get($where = '', $whereParams = array(), $pdo = null) {
        return parent::runquery_fetchMode("select * from " . static::TableName . 
				" where 1=1 " . $where, $whereParams, $pdo);
    }

}
?>
