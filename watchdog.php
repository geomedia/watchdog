<?php
// ===========================================================================================
//
// File: watchdog.php
// Type: watchdog
//
// - Authors: D.Dosimont, G. Foliot
// - Date creation:  2015-10-20
// - Last modification: 2015-10-20
// ===========================================================================================
//opcache_reset();

// ===========================================================================================
//
// ---  Init
//
// ===========================================================================================

// - Set first
$basePath = getcwd();
$car_nl="\n";
setlocale(LC_TIME, 'fr_FR.utf8');
date_default_timezone_set('Europe/Paris');

echo '--------------------------------------------------------------'.$car_nl;
echo '---- Watchdog'.$car_nl;
echo '--------------------------------------------------------------'.$car_nl;
echo 'Current path: '.$basePath.$car_nl;

$handle = fopen($argv[1], 'r');
/*Si on a réussi à ouvrir le fichier*/
if (!$handle){
exit(2);
}

global $watchdogConf;

// - Define

fgets($handle);
fgets($handle);
$watchdogConf['type'] = rtrim(fgets($handle));
fgets($handle);
$watchdogConf['mailTo'] = rtrim(fgets($handle));
fgets($handle);
$watchdogConf['mailFrom'] = rtrim(fgets($handle));
fgets($handle);
$watchdogConf['dbConnectHost'] = rtrim(fgets($handle));
fgets($handle);
$watchdogConf['dbConnectDbName'] = rtrim(fgets($handle));
fgets($handle);
$watchdogConf['dbConnectUser'] = rtrim(fgets($handle));
fgets($handle);
$watchdogConf['dbConnectPasswd'] = rtrim(fgets($handle));

fclose($handle);

$watchdogConf['dbConnectCharacterSet'] = 'UNICODE';
$watchdogConf['currentTime'] = strtotime("now");
$watchdogConf['diffTimeThreshold'] = 60*60*2;			// 2h
$watchdogConf['errorInDbPdo'] = 'none';

echo 'Configuration file = '.$argv[1].$car_nl;
echo 'Type = '.$watchdogConf['type'].$car_nl;
echo 'Mail To = '.$watchdogConf['mailTo'].$car_nl;
echo 'Mail From = '.$watchdogConf['mailFrom'].$car_nl;
echo 'DB Host = '.$watchdogConf['dbConnectHost'].$car_nl;
echo 'DB Name = '.$watchdogConf['dbConnectDbName'].$car_nl;
echo 'DB User = '.$watchdogConf['dbConnectUser'].$car_nl;
echo 'DB Password = '.$watchdogConf['dbConnectPasswd'].$car_nl;

echo 'Time threshold in second: '.$watchdogConf['diffTimeThreshold'].$car_nl;


// - Init postgres

$conn_string = "host=".$watchdogConf['dbConnectHost']." port=5432 dbname=".$watchdogConf['dbConnectDbName']." user=".$watchdogConf['dbConnectUser']." password=".$watchdogConf['dbConnectPasswd']."";

$wdDB_connect = pg_connect($conn_string);
pg_set_client_encoding($wdDB_connect, $watchdogConf['dbConnectCharacterSet']);


// - Test connection

$status = pg_connection_status($wdDB_connect);
if ($status === PGSQL_CONNECTION_OK) {
      echo 'Connect: ready'.$car_nl;
  } else {
      echo 'Connect: ERROR'.$car_nl;
      exit(3);
  }

// ===========================================================================================
//
// ---  Process
//
// ===========================================================================================
//echo $car_nl;

// -------------------------------------------------------------------------------------------
// - Get last item
// -------------------------------------------------------------------------------------------
$lastDateInsert = '2015-01-01 01:00:0.0';

$query_item = "SELECT * FROM public.item ORDER BY daterecup DESC LIMIT 1 OFFSET 0";

// - Query
$result = pg_query($wdDB_connect, $query_item);
$nbItem = pg_num_rows($result);

//echo 'Number of item:'.$nbItem.$car_nl;
while ($row = pg_fetch_array($result))
	{
  	echo "Last record for database";
  	echo 'id='.$row['id'].' ';
  	echo 'datepub='.$row['datepub'].' ';
  	echo 'daterecup='.$row['daterecup'].' ';
  	echo $car_nl;
  	$lastDateInsert = $row['daterecup'];
}

// -------------------------------------------------------------------------------------------
// - Compute date
// -------------------------------------------------------------------------------------------

$lastDateTimeInsert = strtotime($lastDateInsert);
$diffInsert_inSecond = $watchdogConf['currentTime'] - $lastDateTimeInsert;

$report .= 'Current check date : '.date('Y-m-d H:i:s', $watchdogConf['currentTime']).$car_nl;
$report .= 'Last date in db ' .$watchdogConf['type'].' : '.$lastDateInsert.$car_nl;
$report .= 'Last time in db ' .$watchdogConf['type'].' : '. $diffInsert_inSecond.'s'.$car_nl;

echo '--------------------------------------------------------------'.$car_nl;
echo $report;
echo '--------------------------------------------------------------'.$car_nl;


// - Send Notification

if($diffInsert_inSecond > $watchdogConf['diffTimeThreshold']){
	sendNotify($watchdogConf['type'], $report);
        exit(1);
}
return 0;

// ===========================================================================================
//
// ---  Function : sendNotify
//
// ===========================================================================================
function sendNotify($dataBase,$msgContent)
{
// ————————————————————————————————————————————————
// - Init
// ————————————————————————————————————————————————
global $watchdogConf;
$r = '';

// ————————————————————————————————————————————————
// - Send e-mail
// ————————————————————————————————————————————————

$car_nl = "\n";
$to  	 = $watchdogConf['mailTo'];
$subject = '[geomedia-rssaggregate] : application restarted by watchdog ['.$dataBase.']';
$headers = 'From: '.$watchdogConf['mailFrom']. "\r\n" .
			'Reply-To: '.$watchdogConf['mailTo']. "\r\n" .
			'MIME-Version: 1.0' . "\r\n" .
			'Content-type: text/plain; charset=UTF-8' . "\r\n".
			'Content-Transfer-Encoding: 8bit'. "\r\n".
     		'X-Mailer: PHP/' . phpversion();
$message .= '--------------------------------------------------'.$car_nl;
$message .= '-- Report'.$car_nl;
$message .= '--------------------------------------------------'.$car_nl;
$message .= $msgContent.$car_nl;
$message .= 'Threshold has been exceeded. The application rssaggregate will be restarted.';
$message .= $car_nl;
$message .= $car_nl;
date_default_timezone_set('Europe/Paris');
mail($to, $subject, $message, $headers,'-f'.$watchdogConf['mailFrom']);
}



?>
