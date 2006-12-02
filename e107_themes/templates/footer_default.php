<?php
/*
+ ----------------------------------------------------------------------------+
|     e107 website system
|
|     ???Steve Dunstan 2001-2002
|     http://e107.org
|     jalist@e107.org
|
|     Released under the terms and conditions of the
|     GNU General Public License (http://gnu.org).
|
|     $Source: /cvs_backup/e107_0.8/e107_themes/templates/footer_default.php,v $
|     $Revision: 1.1.1.1 $
|     $Date: 2006-12-02 04:36:13 $
|     $Author: mcfly_e107 $
+----------------------------------------------------------------------------+
*/
if (!defined('e107_INIT')) { exit; }

global $eTraffic, $error_handler, $db_time, $sql, $sql2, $mySQLserver, $mySQLuser, $mySQLpassword, $mySQLdefaultdb, $CUSTOMFOOTER, $FOOTER, $e107;

//
// SHUTDOWN SEQUENCE
//
// The following items have been carefully designed so page processing will finish properly
// Please DO NOT re-order these items without asking first! You WILL break something ;)
//
// A Ensure sql and traffic objects exist 
// [Next few ONLY if a regular page; not done for popups]
// B Send the footer templated data
// C Dump any/all traffic and debug information
// [end of regular-page-only items]
// D Close the database connection
// E Themed footer code
// F Configured footer scripts
// G Browser-Server time sync script (must be the last one generated/sent)
// H Final HTML (/body, /html)
// I collect and send buffered page, along with needed headers
// 

//
// A Ensure sql and traffic objects exist
//

if(!is_object($sql)){
	// reinstigate db connection if another connection from third-party script closed it ...
	$sql = new db;
	$sql -> db_Connect($mySQLserver, $mySQLuser, $mySQLpassword, $mySQLdefaultdb);
}
if (!is_object($eTraffic)) {
	$eTraffic = new e107_traffic;
	$eTraffic->Bump('Lost Traffic Counters');
}

unset($fh);


if(varset($e107_popup)!=1){
	//
	// B Send footer template
	//
	parseheader(($ph ? $cust_footer : $FOOTER));
	
	//
	// C Dump all debug and traffic information
	//
	$eTimingStop = microtime();
	global $eTimingStart;
	$rendertime = number_format($eTraffic->TimeDelta( $eTimingStart, $eTimingStop ), 4);
	$db_time    = number_format($db_time,4);
	$rinfo = '';

	if($pref['displayrendertime']){ $rinfo .= "Render time: {$rendertime} second(s); {$db_time} of that for queries. "; }
	if($pref['displaysql']){ $rinfo .= "DB queries: ".$sql -> db_QueryCount().". "; }
	if(isset($pref['display_memory_usage']) && $pref['display_memory_usage']){ $rinfo .= "Memory Usage: ".$e107->get_memory_usage(); }
	if(isset($pref['displaycacheinfo']) && $pref['displaycacheinfo']){ $rinfo .= $cachestring."."; }
	echo ($rinfo ? "\n<div style='text-align:center' class='smalltext'>{$rinfo}</div>\n" : "");


	if ((ADMIN || $pref['developer']) && E107_DEBUG_LEVEL) {
		global $db_debug,$ns;
		echo "\n<!-- DEBUG -->\n";
		if (!isset($ns)) {
			echo "Why did ns go away?<br/>";
			$ns = new e107table;
		}

		$tmp = $eTraffic->Display();
		if (strlen($tmp)) {
			$ns->tablerender('Traffic Counters', $tmp);
		}
		$tmp = $db_debug->Show_Performance();
		if (strlen($tmp)) {
			$ns->tablerender('Time Analysis', $tmp);
		}
		$tmp = $db_debug->Show_SQL_Details();
		if (strlen($tmp)) {
			$ns->tablerender('SQL Analysis', $tmp);
		}
		$tmp = $db_debug->Show_SC_BB();
		if (strlen($tmp)) {
			$ns->tablerender('Shortcodes / BBCode',$tmp);
		}
		$tmp = $db_debug->Show_PATH();
		if (strlen($tmp)) {
			$ns->tablerender('Paths', $tmp);
		}
		$tmp = $db_debug->Show_DEPRECATED();
		if (strlen($tmp)) {
			$ns->tablerender('Deprecated Function Usage', $tmp);
		}
	}
	
	/*
	changes by jalist 24/01/2005:
	show sql queries
	usage: add ?showsql to query string, must be admin
	*/
	
	if(ADMIN && isset($queryinfo) && is_array($queryinfo))
	{
		$c=1;
		$mySQLInfo = $sql->mySQLinfo;
		echo "<table class='fborder' style='width: 100%;'>
		<tr>
		<td class='fcaption' style='width: 5%;'>ID</td><td class='fcaption' style='width: 95%;'>SQL Queries</td>\n</tr>\n";
		foreach ($queryinfo as $infovalue)
		{
			echo "<tr>\n<td class='forumheader3' style='width: 5%;'>{$c}</td><td class='forumheader3' style='width: 95%;'>{$infovalue}</td>\n</tr>\n";
			$c++;
		}
		echo "</table>";
	}

//
// D Close DB connection. We're done talking to underlying MySQL
//
	$sql -> db_Close();  // Only one is needed; the db is only connected once even with several $sql objects

	//
	// Just before we quit: dump quick timer if there is any
	// Works any time we get this far. Not calibrated, but it is quick and simple to use.
	// To use: eQTimeOn(); eQTimeOff();
	//
	$tmp = eQTimeElapsed();
	if (strlen($tmp)) {
		global $ns;
		$ns->tablerender('Quick Admin Timer',"Results: {$tmp} microseconds");
	}
	
} // End of regular-page footer (the above NOT done for popups)

if ($pref['developer']) {
	global $oblev_at_start,$oblev_before_start;
	if (ob_get_level() != $oblev_at_start) {
		$oblev = ob_get_level();
		$obdbg = "<div style='text-align:center' class='smalltext'>Software defect detected; ob_*() level {$oblev} at end instead of ($oblev_at_start). POPPING EXTRA BUFFERS!</div>";
		while (ob_get_level() > $oblev_at_start) {
			ob_end_flush();
		}
		echo $obdbg;
	}
	// 061109 PHP 5 has a bug such that the starting level might be zero or one.
	// Until they work that out, we'll disable this message.
	// Devs can re-enable for testing as needed.
	//
	if (0 && $oblev_before_start != 0) {
		$obdbg = "<div style='text-align:center' class='smalltext'>Software warning; ob_*() level {$oblev_before_start} at start; this page not properly integrated into its wrapper.</div>";
		echo $obdbg;
	}
}

if((ADMIN == true || $pref['developer']) && $error_handler->debug == true) {
	echo "
	<br /><br />
	<div>
		<h3>PHP Errors:</h3><br />
		".$error_handler->return_errors()."
	</div>
	";
}

//
// E Last themed footer code, usually JS
//
if (function_exists('theme_foot')) {
	echo theme_foot();
}

//
// F any included JS footer scripts
//
global $footer_js;
if(isset($footer_js) && is_array($footer_js))
{
	$footer_js = array_unique($footer_js);
	foreach($footer_js as $fname)
	{
		echo "<script type='text/javascript' src='{$fname}'></script>\n";
		$js_included[] = $fname;
	}
}

//
// G final JS script keeps user and server time in sync.
//   It must be the last thing created before sending the page to the user.
//
// see e107.js and class2.php
// This must be done as late as possible in page processing.
$_serverTime=time();
$lastSet = isset($_COOKIE['e107_tdSetTime']) ? $_COOKIE['e107_tdSetTime'] : 0;
if (abs($_serverTime - $lastSet) > 120) {
	/* update time delay every couple of minutes.
	* Benefit: account for user time corrections and changes in internet delays
	* Drawback: each update may cause all server times to display a bit different
	*/
	echo "<script type='text/javascript'>\n";
	echo "SyncWithServerTime('{$_serverTime}');
       </script>\n";
}

//
// H Final HTML
//
echo "</body></html>";

//
// I Send the buffered page data, along with appropriate headers
//
$page = ob_get_clean();

$etag = md5($page);
header("Cache-Control: must-revalidate");
header("ETag: {$etag}");

$pref['compression_level'] == 6;
if(strstr($_SERVER["HTTP_ACCEPT_ENCODING"], "gzip") || strstr($_SERVER['HTTP_USER_AGENT'], "Mozilla")) {
	$browser_support = true;
}
if(ini_get("zlib.output_compression") == false && function_exists("gzencode")) {
	$server_support = true;
}
if($pref['compress_output'] == true && $server_support == true && $browser_support == true) {
	$level = intval($pref['compression_level']);
	$page = gzencode($page, $level);
	header("Content-Encoding: gzip", true);
	header("Content-Length: ".strlen($page), true);
	echo $page;
} else {
	header("Content-Length: ".strlen($page), true);
	echo $page;
}

?>