<?php

function get_json() {
	//Mock a real json response
	//$json = '{"server_info":{"up":true,"up_since":"Thu, 15 Dec 2011 16:00:52 +0100","up_for":22540,"version":"1.0.1"},"server_settings":{"map":"world","pvp":true,"pve":true,"max":20},"players":["p35a","diamant9","johan","malin","inger","mona","frida"],"admins":false,"ops":["p35a","diamant9","kontonr6655"]}';
	//call the real json source
	$json=file_get_contents ( "http://localhost/lib/mss.php" );
	$json_o=json_decode($json);
	return $json_o;
}

function get_online_users() {
	$data = get_json();
	if ($data) {
		$online = $data->players;
		$online['count'] = count($data->players);
		return $online;
	}
}

function get_serverinfo() {
	$data = get_json();
	if ($data) {
		$serverinfo['up'] = ( $data->server_info->up ) ? "online" : "<font color='red'>offline</font>";
		//uptime is only interesting if server is up, else set to n/a
		if ( $data->server_info->up ) {
			$short_time = substr($data->server_info->up_since, 0, -6);
			$serverinfo['up_since'] = $short_time;
			$serverinfo['up_for'] = secondsToWords($data->server_info->up_for);
		} else {
			$serverinfo['up_since'] = "n/a";
			$serverinfo['up_for'] = "n/a";
		}
		$serverinfo['version'] = $data->server_info->version;
		$serverinfo['map'] = $data->server_settings->map;
		$serverinfo['max_players'] = $data->server_settings->max;
		$serverinfo['pvp'] = ($data->server_settings->pvp ) ? "p&aring;" : "av";
		return $serverinfo;
	}
}

function get_toplist() {
	//TODO execute shell command to extract logins from logfiles
	$logdir = "/home/mcraft/bin/minecraft-server";
	$command = "cat $logdir/server.log | grep 'logged in' | cut -d ' ' -f 4 | sort | uniq -c | sort -r";
	exec($command, $result);
	
	//create empty array to hold results
	$toplist = array();
	//split player names and logins from $result and put in toplist as username->logins
	foreach ($result as $user) {
		$user = trim($user);
		$temp_array = explode(" ", $user);
		$toplist[ $temp_array[1] ] = $temp_array[0];
	}

	//find first login in server.log
	$command = "cat $logdir/server.log | grep 'logged in' | cut -d ' ' -f 1 | head -n 1";
	$startdate = exec($command);
	$toplist['startdate'] = $startdate;

	//find last login in server.log
	$command = "cat $logdir/server.log | grep 'logged in' | cut -d ' ' -f 1 | tail -n 1";
	$enddate = exec($command);
	$toplist['enddate'] = $enddate;

	return $toplist;
}

//
//Funktion for att konvertera sekunder till tim, min, sek
//
function secondsToWords($seconds)
{
    /*** start with a blank string ***/
    $hms = "";

    // do the hours first: there are 3600 seconds in an hour, so if we divide
    // the total number of seconds by 3600 and throw away the remainder, we're
    // left with the number of hours in those seconds
    $hours = intval(intval($seconds) / 3600);
    if($hours > 0)
    {
        $hms .= "$hours tim ";
    }
    // dividing the total seconds by 60 will give us the number of minutes
    // in total, but we're interested in *minutes past the hour* and to get
    // this, we have to divide by 60 again and then use the remainder
    $minutes = intval(($seconds / 60) % 60); 
    if($hours > 0 || $minutes > 0)
    {
        $hms .= "$minutes min ";
    }
  
    // seconds past the minute are found by dividing the total number of seconds
    // by 60 and using the remainder
    $seconds = intval($seconds % 60); 
    $hms .= "$seconds s";
    
    return $hms;
}

get_toplist();

?>
