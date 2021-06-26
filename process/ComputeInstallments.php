<?php
require_once '../header.inc.php';
ini_set("display_errors", "On");
require_once '../loan/request/request.class.php';
require_once '../commitment/ExecuteEvent.class.php';

ini_set('max_execution_time', 30000000);
ini_set('memory_limit','4000M');
header("X-Accel-Buffering: no");
ob_start();
set_time_limit(0);

$dt = PdoDataAccess::runquery("select * from LON_requests 
	where RequestID in (
	2096,2097,2103,2144,2150,2175,2176,2179,2188,2199,2200,2207,2212,2213,2215,2217,2218,2220,2221,2222,2223,2229,
	2233,2235,2240,2243,2249,2256,2263,2265,2266,2267,2274,2284,2285,2289,2292,2303,2308,2309,2310,2311,2312,2315,2316,
	2318,2319,2325,2328,2330,2331,2333,2337,2340,2344,2345,2348,2349,2352,2360,2362,2369,2373,2374,2376,2377,2379,2385,2387,
	2396,2397,2399,2400,2402,2403,2406,2408,2415,2417,2419,2421,2425,2428,2436,2443,2448,2452,2458,2464)
    "); 
flush();
ob_flush();
$i=0;
foreach($dt as $row)
{
	LON_installments::ComputeInstallments($RequestID, null, true);
	
	echo $RequestID . " : " . "<br><br>";
	print_r(ExceptionHandler::PopAllExceptions());
	ob_flush();flush();
	
}
die();
?>
