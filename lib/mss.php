<?php
/* Minecraft Server Stats
 * v0.2.1 by Tom Heinan (http://tomheinan.com)
 * 
 * Requires:
 *  - PHP 5.2.0+
 *  - Minecraft 1.2.4+
 * Optional:
 *  - cURL (if you are accessing your files via http)
 */

// set up path to Zend cache
// create a cache object, see if cache exists if so just return cache
// else do everything else.. 
//set_include_path(get_include_path().PATH_SEPARATOR.'/usr/share/php/libzend-framework-php');
require_once ('Cache/Lite.php');

// Set a id for this cache
$cache_id = 'minecache';

// Set a few options
$options = array(
    'cacheDir' => '/tmp/',
    'lifeTime' => 120
);


// enter the location of your minecraft server folder here (and omit the trailing slash)
// i.e. if minecraft_server.jar is at /root/minecraft/minecraft_server.jar, you would enter:
// "/root/minecraft"
define('MC_DIR', "/home/mcraft/bin/minecraft-server");

// you can also enter a URL to a directory containing your server files (including the
// trailing slash), e.g.: "http://www.myserver.com/minecraft/"
// NOTE: for this second method to work, you have to expose server.log, server.properties,
// ops.txt, and admins.txt at that address.
//define('MC_DIR', "http://www.myserver.com/minecraft");

// enter the name of your minecraft executable here, e.g. "minecraft_server.jar"
define('MC_JAR', "minecraft_server.jar");

// this stuff is only required if your minecraft server is running on another host:
//define('MC_TEMP_DIR', "/tmp/mss"); // where you want files cached locally
//define('MC_PRECISION', 60); // time, in seconds, before cache refresh
//define('MC_HOST', "myserver.com"); // ip address or hostname of your server
//define('MC_PORT', 25565); // port number of your server (default is 25565)

/*** stop editing here unless you know what you are doing ***/
	
// Create a Cache_Lite object
$cache = new Cache_Lite($options);

// load cache and see if a cache already exists:
if ($cache_result = $cache->get($cache_id)) {
	// cache hit;
	// we're going to return json, so we need to set some http headers
	header('Cache-Control: no-cache, must-revalidate');
	header('Content-type: application/json');
	echo $cache_result;
	//clean cache 1 in 100 times just to keep tidy
	if(clean_cache()) {
		$cache->clean();
	}
} else {
	// we'll define an array to contain our data in a format-agnostic manner...
	$data = array();
	
	// find out if minecraft is running or not
	$up = false;
	if (isRemote()) {
		$fp = fsockopen(MC_HOST, MC_PORT, $errno, $errstr, 30);
		if ($fp) {
		  $up = true;
			
			if (!file_exists(MC_TEMP_DIR)) {
				mkdir(MC_TEMP_DIR);
			}
			
			// check the last time the cache was refreshed
			$timestamp = 0;
			if (file_exists(MC_TEMP_DIR."/timestamp.txt")) {
				$ts = file(MC_TEMP_DIR."/timestamp.txt", FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
				$timestamp = intval($ts[0]);
			}
			
			// if necessary, refresh the cache with a multithreaded curl request
			if (($timestamp == 0) || ((intval(date('U')) - $timestamp) > MC_PRECISION)) {
				$handles = array(
					'log' => curl_init(),
					'props' => curl_init(),
					'admins' => curl_init(),
					'ops' => curl_init()
				);
				$file_pointers = array(
					'log' => fopen(MC_TEMP_DIR."/server.log.tmp", 'w'),
					'props' => fopen(MC_TEMP_DIR."/server.properties.tmp", 'w'),
					'admins' => fopen(MC_TEMP_DIR."/admins.txt.tmp", 'w'),
					'ops' => fopen(MC_TEMP_DIR."/ops.txt.tmp", 'w')
				);
				$mh = curl_multi_init();
	
				curl_setopt($handles['log'], CURLOPT_URL, MC_DIR."server.log");
				curl_setopt($handles['props'], CURLOPT_URL, MC_DIR."server.properties");
				curl_setopt($handles['admins'], CURLOPT_URL, MC_DIR."admins.txt");
				curl_setopt($handles['ops'], CURLOPT_URL, MC_DIR."ops.txt");
	
				foreach ($handles as $name => &$handle) {
					curl_setopt($handle, CURLOPT_FOLLOWLOCATION, true);
					curl_setopt($handle, CURLOPT_TIMEOUT, 30);
					curl_setopt($handle, CURLOPT_FILE, $file_pointers[$name]);
					curl_multi_add_handle($mh, $handle);
				}
	
				$active = null;
				do {
					$mrc = curl_multi_exec($mh, $active);
				} while ($mrc == CURLM_CALL_MULTI_PERFORM);
	
				while ($active && $mrc == CURLM_OK) {
					if (curl_multi_select($mh) != -1) {
						do {
					$mrc = curl_multi_exec($mh, $active);
				    } while ($mrc == CURLM_CALL_MULTI_PERFORM);
					}
				}
	
				foreach ($handles as $name => &$handle) {
					curl_multi_remove_handle($mh, $handle);
					curl_close($handle);
					fclose($file_pointers[$name]);
				}
				curl_multi_close($mh);
				
				copy(MC_TEMP_DIR."/server.log.tmp", MC_TEMP_DIR."/server.log");
				copy(MC_TEMP_DIR."/server.properties.tmp", MC_TEMP_DIR."/server.properties");
				copy(MC_TEMP_DIR."/admins.txt.tmp", MC_TEMP_DIR."/admins.txt");
				copy(MC_TEMP_DIR."/ops.txt.tmp", MC_TEMP_DIR."/ops.txt");
	
				$fh = fopen(MC_TEMP_DIR."/timestamp.txt", 'w');
				fwrite($fh, date('U')."\n");
				fclose($fh);
			}
		}
	} else {
		$output = array();
		exec('ps aux | grep "'.MC_JAR.'"', $output);
		foreach ($output as $line) {
			if (preg_match("/java\s+/", $line)) {
				$up = true;
				break;
			}
		}
	}
	
	// get a new instance of the Minecraft log parser
	$mc = new Minecraft();
	
	$data['server_info'] = array();
	$data['server_settings'] = array();
	if ($up) {
		$data['server_info']['up'] = true;
		$data['server_info']['up_since'] = date("r", $mc->up_since);
		$data['server_info']['up_for'] = $mc->up_for;
		$data['server_info']['version'] = $mc->version;
		$data['players'] = $mc->players;
		$data['server_settings']['map'] = $mc->map;
		$data['server_settings']['pvp'] = $mc->pvp;
		$data['server_settings']['pve'] = $mc->pve;
		$data['server_settings']['max'] = $mc->max_players;
		$data['admins'] = $mc->admins;
		$data['ops'] = $mc->ops;
	} else {
		$data['server_info']['up'] = false;
	}
	
	// we're going to return json, so we need to set some http headers
	header('Cache-Control: no-cache, must-revalidate');
	header('Content-type: application/json');
	
	// here is where the magic happens
	$result = json_encode($data);
	//first save data to cache
	$cache->save($result, $cache_id);
	//and then send it to browser
	echo $result;
} //end else - for cache miss

// the Minecraft class represents the current instance of the server since the last time
// it was started.  obviously, the longer the server is up, the longer it will take the script
// to parse the logfile.
class Minecraft {
	var $version;
	var $up_since;
	var $up_for;
	var $map;
	
	var $players = array();
	var $admins = array();
	
	var $pvp = false;
	var $pve = false;
	var $max_players;
	var $whitelist;
	
	function Minecraft() {
		// open the log file
		$logfile_path = (isRemote()) ? (MC_TEMP_DIR."/server.log") : MC_DIR."/server.log";
		$logfile = new ReverseFile($logfile_path);
		
		$events = array();
		$list = null;
		
		// process the current server instance
		while (!$logfile->sof()) {
			$line = $logfile->get_line();
			$matches = array();
			
			// if a player has logged in, register a "connect" event
			if (preg_match("/\[info\] (\w+) .*logged in/i", $line, $matches)) {
				array_push($events, array("method" => "connect", "data" => $matches[1]));
			}
			
			// if a player has logged out, register a "disconnect" event
			if (preg_match("/\[info\] (\w+) lost connection/i", $line, $matches)) {
				array_push($events, array("method" => "disconnect", "data" => $matches[1]));
			}
			
			// check for spurious connections
			if (preg_match("/connected players: (.*)/i", $line, $matches) && $list == null) {
				$list = explode(", ", $matches[1]);
				array_push($events, array("method" => "list", "data" => $matches[1]));
			}
			
			// save the name of the currently loaded level
			if (preg_match("/preparing level \"(.*)\"$/i", $line, $matches)) {
				$this->map = $matches[1];
			}
			
			// if this is the last line of the instance, break the loop
			if (preg_match("/\[info\] starting minecraft server version/i", $line)) {
				preg_match("/^(.*)\s+\[/", $line, $matches);
				$this->up_since = strtotime($matches[1]);
				$this->up_for = time() - $this->up_since;
				
				preg_match("/version\s+(.*)$/i", $line, $matches);
				$this->version = $matches[1];
				break;
			}
		}
		
		// neglecting file descriptors is bad, mmkay
		$logfile->close();
		
		// review the connection events and figure out who's currently online
		$events = array_reverse($events);
		for ($i = 0; $i < count($events); $i++) {
			$event = $events[$i];
			if ($event['method'] == "connect") {
				array_push($this->players, $event['data']);
			} else if ($event['method'] == "disconnect") {
				foreach ($this->players as $key => $player) {
					if ($player == $event['data']) {
						unset($this->players[$key]);
					}
				}
			} else if ($event['method'] == "list") {
				$this->players = $list;
			}
		}
		$this->players = array_values($this->players);
		
		// get the administrators
		$admins_path = (isRemote()) ? MC_TEMP_DIR."/admins.txt" : MC_DIR."/admins.txt";
		$this->admins = file($admins_path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
		
		// get the ops
		$ops_path = (isRemote()) ? MC_TEMP_DIR."/ops.txt" : MC_DIR."/ops.txt";
		$this->ops = file($ops_path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
		
		// get miscellaneous other data
		$props_path = (isRemote()) ? MC_TEMP_DIR."/server.properties" : MC_DIR."/server.properties";
		$properties = file($props_path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
		foreach ($properties as $property) {
			$matches = array();
			if (preg_match("/^pvp=(\w+)/i", $property, $matches)) {
				if (preg_match("/true/i", $matches[1])) {
					$this->pvp = true;
				}
			}
			if (preg_match("/^spawn-monsters=(\w+)/i", $property, $matches)) {
				if (preg_match("/true/i", $matches[1])) {
					$this->pve = true;
				}
			}
			if (preg_match("/^max-players=(\d+)/i", $property, $matches)) {
				$this->max_players = (int) $matches[1];
			}
		}
	}
}

// the ReverseFile class allows us to read a file backwards line-by-line without having to
// read the whole thing into memory first...
// note: most of this code shamelessly stolen from Raymond Kolbe/Steve Weet;
// see http://www.raymondkolbe.com/2006/12/19/21/ for details.
class ReverseFile {
	var $filename;
	var $filehandle;
	var $filepos;
	
	function ReverseFile($filename) {
		$this->filename = $filename;
		$this->filehandle = fopen($this->filename, "r") or die ("Could not open file $this->filename.\n");
		
		// search for EOF
		if (!(fseek($this->filehandle, 0, SEEK_END) == 0)) {
			die ("Could not find end of file in $this->filename.\n");
		}
			
		// store the file position
		$this->filepos = ftell($this->filehandle);
		
		// check that file is not empty or doesn't contain a single newline
		if ($this->filepos < 2) {
			die ("File is empty.\n");
		}
		
		// position file pointer just before final newline (skip EOF)
		$this->filepos -= 1;
	}
	
	function get_line(){
		$pos = $this->filepos - 1;
		$ch = "";
		$line = "";
		while ($ch != "\n" && $pos>= 0){
			fseek($this->filehandle, $pos);
			$ch = fgetc($this->filehandle);
			
			// decrement out pointer and prepend to the line if we have not hit the new line
			if ($ch != "\n") {
				$pos = $pos -1;
				$line = $ch . $line;
			}
		}
		$this->filepos = $pos;
		return $line."\n";
	}
	
	function sof() {
		return ($this->filepos <= 0);
	}
	
	function close() {
		return fclose($this->filehandle);
	}
}

function isRemote() {
	if (preg_match("/^http:\/\//i", MC_DIR)) {
		return true;
	} else {
		return false;
	}
}

function clean_cache() {
	$r = rand(0,100);
	//clean cache if random is 76 :)
	if ($r == 76) {
		return true;
	}
	else {
		return false;
	}
}
?>
