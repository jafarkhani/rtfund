<?php
//-------------------------
// programmer:	Sh.Jafarkhani
// Create Date:	86.09
//-------------------------
date_default_timezone_set('Asia/Tehran');

class DateModules
{
	static private $g_days_in_month = array(31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31); 
	static $j_days_in_month = array(31, 31, 31, 31, 31, 31, 30, 30, 30, 30, 30, 29);
	
	static $WeekDays = array(
		"Saturday" => "1", 
		"Sunday" => "2", 
		"Monday" => "3",
		"Tuesday" => "4",
		"Wednesday" => "5",
		"Thursday" => "6",
		"Friday" => "7"
	);
	
	static $JWeekDays = array(
		"1" => "دوشنبه",
		"2" => "سه شنبه",
		"3" => "چهارشنبه",
		"4" => "پنجشنبه",
		"5" => "جمعه",
		"6" => "شنبه",
		"7" => "یکشنبه",
	);
	/**
	 * @param Y-m-d $date
	 * @return Y-m-d $date
	 */
	static function miladi_to_shamsi($date, $delimiter = "/")
	{
		if($date == "0000-00-00" || $date == "0000-00-00 00:00:00")
			return '';
		
		$ch = substr($date,2,1);
        if( $ch == '/' || $ch == '-' ) {
            $arr = preg_split('/[\-\/]/',$date);
            $t = $arr[0];
            $arr[0] = $arr[2];
            $arr[2] = $t;
            $date = implode($ch,$arr);
        }

		$yy=substr($date,0,4);
		if($yy < 1500)
			return $date;

		$mm=substr($date,5,2);
		$dd=substr($date,8,2);
		
		$sh = DateModules::ConvertX2SDate($dd,$mm,$yy);
		
		if ($sh[1]<10)
			$sh[1]='0'.$sh[1];
		if ($sh[0]<10)
			$sh[0]='0'.$sh[0];
		
		return $sh[2].$delimiter.$sh[1].$delimiter.$sh[0];
	}
	
	/**
	 * @param Y-m-d $date
	 * @return Y-m-d $date
	 */
	static function shamsi_to_miladi($date, $seperator = '/')
	{
		if($date == "" || $date == "0000-00-00")
			return '0000-00-00';
            
		$ch = substr($date,2,1);
        if( $ch == '/' || $ch == '-' ) {
            $arr = preg_split('/[\-\/]/',$date);
            $t = $arr[0];
            $arr[0] = $arr[2];
            $arr[2] = $t;
            $date = implode($ch,$arr);
        }
			
		//....................
		$arr = preg_split('/[\-\/]/',$date);
		if(strlen($arr[1]) == 1 ) $arr[1] = "0".$arr[1] ; 
		if(strlen($arr[2]) == 1 ) $arr[2] = "0".$arr[2] ; 
		$date = implode($seperator,$arr);  
		//.....................
		$yy=substr($date,0,4);
		if($yy > 1900)
			return preg_replace ('/[\-\/]/', $seperator, $date);

		$mm=substr($date,5,2);
		$dd=substr($date,8,2);
		$sh = DateModules::ConvertS2XDate($dd,$mm,$yy);
		
		if ($sh[1]<10)
			$sh[1]='0'.$sh[1];
		if ($sh[2]<10)
			$sh[2]='0'.$sh[2];
		
		return $sh[0] . $seperator . $sh[1] . $seperator . $sh[2];
	}
	
	static function shNow($seperator = "/")
	{
		$now = getdate();
		$now = DateModules::ConvertX2SDate($now["mday"],$now["mon"],$now["year"]);
		$yy=$now[2];
		$mm=$now[1];
		$dd=$now[0];
		if ($dd < 10) $dd = '0' . $dd;
		if ($mm < 10) $mm = '0' . $mm;
		return $yy . $seperator . $mm . $seperator . $dd;
	}

	static function Now()
	{
		return date("Y-m-d");
	}
	
	static function NowTime()
	{
		date_default_timezone_set("Asia/Tehran"); 
		return date("H:i:s");
	}
	
	static function NowDateTime()
	{
		return date ("Y-m-d H:i:s");
	}
	 
	private static function ConvertX2SDate($g_d, $g_m, $g_y) 
	{ 
   		$div = create_function('$a, $b', 'return (int) ($a / $b);'); 
		$gy = $g_y-1600; 
	   	$gm = $g_m-1; 
		$gd = $g_d-1; 
	   	$g_day_no = 365*$gy+$div($gy+3, 4)-$div($gy+99, 100)+$div($gy+399, 400);
	   	 
	   	for ($i=0; $i < $gm*1; ++$i) 
			$g_day_no += DateModules::$g_days_in_month[$i]; 
	   	if ($gm>1 && (($gy%4==0 && $gy%100!=0) || ($gy%400==0))) 
			$g_day_no++; /* leap and after Feb */ 
		$g_day_no += $gd; 
	   	$j_day_no = $g_day_no-79; 
	   	$j_np = $div($j_day_no, 12053); /* 12053 = 365*33 + 32/4 */ 
		$j_day_no = $j_day_no % 12053; 
	   	$jy = 979+33*$j_np+4*$div($j_day_no, 1461); /* 1461 = 365*4 + 4/4 */ 
	   	$j_day_no %= 1461; 
		if ($j_day_no >= 366) { 
			$jy += $div($j_day_no-1, 365); 
			$j_day_no = ($j_day_no-1)%365; 
		} 
	   	for ($i = 0; $i < 11 && $j_day_no >= DateModules::$j_days_in_month[$i]; ++$i) 
			$j_day_no -= DateModules::$j_days_in_month[$i]; 
	   	$jm = $i+1; 
		$jd = $j_day_no+1; 
	   	return array($jd, $jm, $jy); 
	}
		
	private static function ConvertS2XDate($j_d, $j_m, $j_y) 
	{ 
   		$div = create_function('$a, $b', 'return (int) ($a / $b);'); 
	   	$jy = $j_y-979; 
	   	$jm = $j_m-1; 
		$jd = $j_d-1; 
	   	$j_day_no = 365*$jy + $div($jy, 33)*8 + $div($jy%33+3, 4); 
		for ($i=0; $i < $jm; ++$i) 
			$j_day_no += DateModules::$j_days_in_month[$i]; 
	   	$j_day_no += $jd; 
	   	$g_day_no = $j_day_no+79; 
	   	$gy = 1600 + 400*$div($g_day_no, 146097); /* 146097 = 365*400 + 400/4 - 400/100 + 400/400 */ 
		$g_day_no = $g_day_no % 146097; 
	   	$leap = true; 
		if ($g_day_no >= 36525) { /* 36525 = 365*100 + 100/4 */ 
			$g_day_no--; 
			$gy += 100*$div($g_day_no, 36524); /* 36524 = 365*100 + 100/4 - 100/100 */ 
			$g_day_no = $g_day_no % 36524; 
			if($g_day_no >= 365) 
				$g_day_no++; 
			else 
				$leap = false; 
		} 
	   	$gy += 4*$div($g_day_no, 1461); /* 1461 = 365*4 + 4/4 */ 
		$g_day_no %= 1461; 
	   	if ($g_day_no >= 366) { 
			$leap = false; 
			$g_day_no--; 
			$gy += $div($g_day_no, 365); 
			$g_day_no = $g_day_no % 365; 
	   	} 
		for ($i = 0; $g_day_no >= DateModules::$g_days_in_month[$i] + ($i == 1 && $leap); $i++) 
			$g_day_no -= DateModules::$g_days_in_month[$i] + ($i == 1 && $leap); 
		$gm = $i+1; 
		$gd = $g_day_no+1; 
		//return sprintf("%04d%02d%02d", $gy, $gm, $gd);
  		return array($gy, $gm, $gd); 
	} 

	/**
	 * @param y-m-d $gdate1
	 * @param y-m-d $gdate2
	 */
	static function GDateMinusGDate($gdate1, $gdate2) 
	{
		$gdate1 = substr($gdate1,0,10);
		$gdate2 = substr($gdate2,0,10);
		
        $gdate_array1 = preg_split('/[\-\/]/',$gdate1);
		$gdate_array2 = preg_split('/[\-\/]/',$gdate2);
		
		if(count($gdate_array1) < 3 || count($gdate_array2) < 3)
			return 0;
		
		$dist1 = 1970 - $gdate_array1[0];
		$dist2 = 1970 - $gdate_array2[0];
		$diff_years = 0;
		if( $dist1>=0 || $dist2>=0 ) {
			$diff_years = ($dist1>$dist2)? $dist1 : $dist2;
			$diff_years = floor( ($diff_years + 5)/4 )*4;
		}
		$gtime1 = mktime(0, 0, 0, $gdate_array1[1], $gdate_array1[2], $gdate_array1[0] + $diff_years);
		$gtime2 = mktime(0, 0, 0, $gdate_array2[1], $gdate_array2[2], $gdate_array2[0] + $diff_years);
		return round(($gtime1 - $gtime2) / 86400); //number of days
	}
	
	/**
	 * @param y-m-d $gdate1
	 * @param y-m-d $gdate2
	 */
	static function JDateMinusJDate($jdate1, $jdate2) 
	{
		$gdate1 = self::shamsi_to_miladi($jdate1);
		$gdate2 = self::shamsi_to_miladi($jdate2);
		
		//$gdate1 = date_create($gdate1);
		//$gdate2 = date_create($gdate2);
		
		//$diff = date_diff($gdate2, $gdate1, true);
		//return $diff->y * 365.25 + $diff->m * 30 + $diff->d ;
		
		return self::GDateMinusGDate($gdate1, $gdate2);
	}


	/**
	 * تبديل روز ماه سال به روز
	 */
	static function ymd_to_days($year, $month, $day)
	{
	    if($day==null) $day = 0 ;
	    if($month==null) $month = 0 ;
	    if($year==null) $year = 0 ;
		return $year * 365.25 + $month * 30.4375 + $day ;
	}
	
	/**
	 * تبديل روز به روز - ماه - سال
	 */
	static function day_to_ymd($days, &$year, &$month, &$day)
	{
	    $year = floor($days / 365.25);
	    $days -= $year * 365.25;
	    $month = floor($days / 30.4375);
	    $days -= $month * 30.4375;
	    $day = round($days) ;
	    if ($day >= 30) {
	    	$month++;
	    	$day = 0;
	    }
	    if ($month == 12) {
	    	$year++;
	    	$month = 0;
	    }
	}
	// 0 = equal
	// 1 $date1 > $date2
	// -1 $date1 < $date2	
	static function CompareDate($date1, $date2) 
	{
		if ($date1 == null || $date1 == "")
			return -1 ;
		if ($date2 == null || $date2 == "")	
			return 1;
		list($year1, $month1, $day1) = preg_split('/[\-\/]/', $date1);
		list($year2, $month2, $day2) = preg_split('/[\-\/]/', $date2);
		if ($year1 > $year2)
			return 1;
		if ($year1 < $year2)
			return -1;
		if ($month1 > $month2)	
			return 1;
		if ($month1 < $month2)	
			return -1;
		if ($day1 > $day2)	
			return 1;
		if ($day1 < $day2)	
			return -1;
		return 0;	
		
	} 

	/**
	 * @param Y-m-d $gdate
	 */
    static function AddToGDate($gdate, $dayplus=0, $monthplus=0, $yearplus=0) 
    {
	    $gdate_array = preg_split('/[\-\/]/',$gdate);
	    $gtime = mktime(0, 0, 0, $gdate_array[1]+$monthplus, $gdate_array[2]+$dayplus, $gdate_array[0]+$yearplus);
	    return date("Y-m-d",$gtime);
	}
	
	static function AddToJDate($jdate, $dayplus=0, $monthplus=0, $yearplus=0) 
    {
		$dayplus = $dayplus*1;	
		$monthplus = $monthplus*1;
		$yearplus = $yearplus*1;

		if($dayplus > 0)
		{
			$gdate = self::shamsi_to_miladi($jdate);
			$gdate_array = preg_split('/[\-\/]/',$gdate);
			$gtime = mktime(0, 0, 0, $gdate_array[1], $gdate_array[2]+$dayplus, $gdate_array[0]);
			$jdate = self::miladi_to_shamsi(date("Y-m-d",$gtime));
		}
		
		$arr = preg_split('/[\-\/]/',$jdate);
		
		$year = $arr[0]*1 + floor(($arr[1]*1 + $monthplus) / 12) + $yearplus;
		$monthplus = ($arr[1]*1 + $monthplus)%12;
		if($monthplus == 0)
		{
			$year--;
			$monthplus = 12;
		}
		$dayplus = $arr[2];

		return $year . "-" . $monthplus . "-" . $dayplus;
	}

	/**
	 * مشخص مي کند که دو تاريخ در يک سال هستند يا خير
	 * 
	 * @param miladi_date $gdate1
	 * @param miladi_date $gdate2
	 * 
	 * @return boolean
	 */
	static function similar_year($gdate1, $gdate2)
	{
		$year_changed = false;
		list($day1,$month1,$year1)=preg_split('/\//',DateModules::Miladi_to_Shamsi($gdate1));
		list($day2,$month2,$year2)=preg_split('/\//',DateModules::Miladi_to_Shamsi($gdate2));
		
		if($year1!=$year2)
			$year_changed = true ;
			
		return $year_changed ;
	}

	/**
	 * @param Y-m-d $date1
	 * @param Y-m-d $date2
	 */
	static function getDateDiff($date1,$date2)
	{
				
		$date1 = DateModules::miladi_to_shamsi($date1) ; 
		$date2 = DateModules::miladi_to_shamsi($date2) ; 
		
		$year1 = substr($date1,0,4);
		$month1 = substr($date1,5,2);
		$day1 = substr($date1,8,2);
		
		$year2 = substr($date2,0,4);
		$month2 = substr($date2,5,2);
		$day2 = substr($date2,8,2);
		
		 
		return ($year1 - $year2) * 360 + ($month1 - $month2) * 30 + ($day1 - $day2) ;
			
	}

	static function GetYear($date)
	{
		if(strpos($date, "-") !== false)
			$arr = preg_split('/-/', $date);
		else
			$arr = preg_split('/\//', $date);

		if(strlen($arr[0]) == 4)
			return $arr[0];
		return $arr[2];
	}

	static function GetMonth($date)
	{
		if(strpos($date, "-") !== false)
			$arr = preg_split('/-/', $date);
		else
			$arr = preg_split('/\//', $date);

		return $arr[1];
	}
	
	static function GetDay($date)
	{
		if(strpos($date, "-") !== false)
			$arr = preg_split('/-/', $date);
		else
			$arr = preg_split('/\//', $date);

		return $arr[2];
	}

	static function GetMonthName($MonthNumber)
	{
		$months_title = array(1=>'فروردين',
                          2=>'ارديبهشت',
                          3=>'خرداد',
                          4=>'تير',
                          5=>'مرداد',
                          6=>'شهريور',
                          7=>'مهر',
                          8=>'آبان',
                          9=>'آذر',
                          10=>'دي',
                          11=>'بهمن',
                          12=>'اسفند');

		$MonthNumber = $MonthNumber * 1;
		return $months_title[$MonthNumber];
	}

	static function IsDate($date)
	{
		if(strlen($date) == 10 && in_array($date[4],array('/', '-')) &&  in_array($date[7],array('/', '-')))
			return true;
		return false;
	}
	
	static function GetWeekDay($jdate, $format = "l")
	{
		return date_format(new DateTime(self::shamsi_to_miladi($jdate)), $format);
	}
	
	static function GetJWeekDay($jdate)
	{
		return self::$JWeekDays[ self::GetWeekDay($jdate, "N") ];
	}
	
	static function DateToString($SHdate)
	{
		$year = substr($SHdate, 1, 3); 
		$month = substr($SHdate, 5, 2);
		$day = substr($SHdate, 8, 2);
		return self::Convert3Digit($day, true) . self::GetMonthName((int)$month) . " ماه هزار و " . 
				self::Convert3Digit($year);
	}
	
	private static function Convert3Digit($digit, $tail = false)
	{
		$number_array = array(
			1 => 'يک',
			2 => 'دو',
			3 => 'سه',
			4 => 'چهار',
			5 => 'پنج',
			6 => 'شش',
			7 => 'هفت',
			8 => 'هشت',
			9 => 'نه',

			10 => 'ده',
			11 => 'يازده',
			12 => 'دوازده',
			13 => 'سيزده',
			14 => 'چهارده',
			15 => 'پانزده',
			16 => 'شانزده',
			17 => 'هفده',
			18 => 'هيجده',
			19 => 'نوزده',

			20 => 'بيست',
			30 => 'سي',
			40 => 'چهل',
			50 => 'پنجاه',
			60 => 'شصت',
			70 => 'هفتاد',
			80 => 'هشتاد',
			90 => 'نود',


			0 => 'يکصد',
			200 => 'دويست',
			300 => 'سيصد',
			400 => 'چهارصد',
			500 => 'پانصد',
			600 => 'ششصد',
			700 => 'هفتصد',
			800 => 'هشتصد',
			900 => 'نهصد'
			);

		$digit = (int)$digit;
		$three_digit_string = "";
		if($digit > 100)
		{
			$three_digit_string .= $number_array[floor($digit / 100) * 100] . " و ";
			$digit %= 100;  
		}
		if($digit == 0)
			$three_digit_string = substr($three_digit_string,0, strlen($three_digit_string)-3);
		else if($digit < 20)
			$three_digit_string .= $number_array[$digit];
		else
		{
			if ($digit > 0) {
				$three_digit_string .= $number_array[floor($digit / 10) * 10] . " و ";
				$digit %= 10;
				
				if ($digit > 0) {
					$three_digit_string .= $number_array[$digit];
				}
				else
				{
					$three_digit_string = substr($three_digit_string, 0, strlen($three_digit_string)-3);
				}
			}
			else
			{
				$three_digit_string = substr($three_digit_string, 0, strlen($three_digit_string)-3);
			}
			
		}
		if($tail)
		{
			if(substr($three_digit_string, strlen($three_digit_string)-4) == "سه")
				$three_digit_string = substr($three_digit_string, 0, strlen($three_digit_string)-2) . "وم ";
			else if(substr($three_digit_string, strlen($three_digit_string)-5) == " سی")
				$three_digit_string .= " ام ";
			else
				$three_digit_string .= "م ";
		}
		
		return $three_digit_string;
	}	   
// تاریخ شمسی را در فرمت صحیح آن بر می گرداند.
	static function GetDateFormat($SHDate,$Format="Y/M/D")
	{
				
		$Arr = preg_split('/[\-\/]/',$SHDate);
		$year = $Arr[0];
		$month = $Arr[1];
		$day = $Arr[2];
		if(strlen($Arr[0]) < 4 )
		{
			$year = "13".$Arr[0];
		}
		if(strlen($Arr[1]) < 2 )
		{
			$month = "0".$Arr[1];
		}
		if(strlen($Arr[2]) < 2 )
		{
			$day = "0".$Arr[2];
		}
		
		$SHDate = $year."/".$month."/".$day ; 
		
		return $SHDate ; 			
		
	}

	/**
	 *
	 * @param type $jyear
	 * @return  تعیین می کند آیا سال شمسی کبیسه است یا نه 
	 */
	static function YearIsLeap($jyear){
	  if ((self::GetMod(($jyear-22) , 33) == 0) or (( self::GetMod(( self::GetMod(($jyear-22) ,33)) , 32) <> 0) and ((self::GetMod( self::GetMod(($jyear-22) , 33) , 4 ) == 0))))
		return 1;
	  return  0;
	}	 

	//Returns the remainder of dividing the dividend (x) by the divisor (y).
	static function GetMod($dividend,$divisor){		
		$rest = $dividend - ($divisor * floor($dividend / $divisor));
		return $rest;	
	}
	
	/**
	 *
	 * @param int $jyear
	 * @param int $jmonth
	 * @return تعداد روزهای ماه شمسی را برمی گرداند 
	 */
	static function DaysOfMonth($jyear, $jmonth){
		
		$month_length = 0;
		if($jmonth <= 6 ) {
			$month_length =  31;
		}
		else {
			$month_length =  30;
			if($jmonth == 12 && !self::YearIsLeap($jyear)) {
				$month_length = 29;
			}
		}
		return $month_length;
	}	  

	static function FirstGDateOfYear()
	{
		$now = self::shNow();
		$cur_year = substr($now,0,4);
		return $cur_year . "/01/01";
	}
	
	static function lastJDateOfYear($jyear, $delimiter = "/"){
		
		$day = self::DaysOfMonth($jyear, 12);
		return $jyear . $delimiter . "12" . $delimiter . $day;
	}	  

	static function CurrentTime() {
        return date('H:i:s');
    }

	static function GetDiffInMonth($jdate1, $jdate2){
		
        $year1 = self::GetYear($jdate1);
        $month1 = self::GetMonth($jdate1);

        $year2 = self::GetYear($jdate2);
        $month2 = self::GetMonth($jdate2);

        return ($year2-$year1)*12 + ($month2-$month1);
    }	
    
    static function CalculateDuration($start_date,$end_date) {

	      $start_date = DateModules::miladi_to_shamsi($start_date); 
	      $end_date = DateModules::miladi_to_shamsi($end_date); 

	      $start_date_day = substr($start_date,8,2);
	      $start_date_month = substr($start_date,5,2);
	      $start_date_year = substr($start_date,0,4);

	      $end_date_day = substr($end_date,8,2);
	      $end_date_month = substr($end_date,5,2);
	      $end_date_year = substr($end_date,0,4);
	      $duration = ($end_date_year - $start_date_year) * 360 +
			  ($end_date_month - $start_date_month) * 30 +
			  ($end_date_day - $start_date_day) ;

	      return $duration;
	}
	
	
}

class TimeModules {
	
	static function CurrentTime() {
        return date('H:i:s');
    }
	
	/**
	 *
	 * @param type $time1
	 * @param type $time2
	 * @return array(hours, minutes, seconds)
	 */
	static function TimeDiff($time1, $time2) {
       $t1 = strtotime($time1);
	   $t2 = strtotime($time2);
	   
	   $seconds = $t2 - $t1;
	   return SecondsToTime($seconds);
    }
	
	/**
	 *
	 * @param type $seconds 
	 * @return array(hours, minutes, seconds)
	 */
	static function SecondsToTime($seconds){
		
		$hours = floor($seconds / 3600);
	   
		$seconds = $seconds - $hours*3600;
		$minutes = floor( $seconds / 60);
	   
		$seconds = $seconds - $minutes*60;
		return array(str_pad($hours, 2, "0", STR_PAD_LEFT), 
			str_pad($minutes, 2, "0", STR_PAD_LEFT), 
			str_pad($seconds, 2, "0", STR_PAD_LEFT));
	}

	static function ShowTime($arr){

		if($arr[0] == "00" && $arr[1] == "00")
			return "";
		return $arr[0] . ":" . $arr[1];
	}	
}
?>