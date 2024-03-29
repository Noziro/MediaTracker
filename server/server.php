<?php

$pl_timer_start = hrtime(True);

// GLOBAL VARIABLES

date_default_timezone_set('UTC');

include "keyring.php";
// keys.php contains potentially sensitive information such as the MYSQL_HOST/USER/PASS/DB/PORT variables.

$db = new mysqli(MYSQL_HOST, MYSQL_USER, MYSQL_PASS, MYSQL_DB, MYSQL_PORT);

$stmt = $db->prepare('SET @@global.time_zone = "+00:00"');
$stmt->execute();

session_start();

// TODO - this should be cleaned up or deleted
#set_error_handler("errorHandler");
#function errorHandler($errno, $errstr, $errfile, $errline)
#{
#    error_log("$errstr in $errfile:$errline");
#	echo $errstr.'<br><br>';
#    #header('Location: /500');
#}



// GENERIC FUNCTIONS

function to_int($s) {
	return intval(round($s));
}

// Formats text that is retrieved from SQL database.
function format_user_text($s) {
	// encode BB here
	return nl2br(htmlspecialchars($s));
}



// AUTH SYSTEM

class Authentication {
	private $db;
	
	public function Authentication() {
        $this->db = $GLOBALS['db']; // This is terrible. Don't do this.
	}
	
	// Function called by user attempting login. Returns BOOL for success/fail.
	public function login(string $post_name, string $post_pass) {
		// Username is case insensitive.
		$post_name_normalized = strtolower($post_name);

		// Check user exists & get info
		$stmt = sql('SELECT id, username, password FROM users WHERE username=?', ['s', $post_name_normalized]);
		if(!$stmt['result'] || $stmt['rows'] < 1) {
			return false;
		}
		
		$user = $stmt['result'][0];
		
		// Validate password
		$valid = password_verify($post_pass, $user['password']);
		if (!$valid) {
			return false;
		}

		// Set expiry date in Unix time for use in database and cookies
		$offset = 90 * 24 * 60 * 60; // days * hours * minutes * seconds
		$expiry = time() + $offset;
		
		// Set other variables
		$session = $this->generate_session_id();
		$user_ip = $_SERVER['REMOTE_ADDR'];

		// Create new user session
		$stmt = sql('INSERT INTO sessions (id, user_id, expiry, user_ip) VALUES (?, ?, ?, ?)', ['siis', $session, $user['id'], $expiry, $user_ip]);
		if(!$stmt['result']) {
			return false;
		}
		
		setcookie('session', $session, $expiry, '/');
		return true;
	}
	
	// Function called by user attempting register. Returns BOOL for success/fail.
	public function register(string $post_name, string $post_pass, string $email = '') {
		$post_name_normalized = strtolower($post_name);

		// Check for existence & get info
		$stmt = sql('SELECT id FROM users WHERE username=?', ['s', $post_name_normalized]);
		if (!$stmt['result'] || $stmt['rows'] > 0) {
			return false;
		}

		$user = $stmt['result'][0];
		
		// Hash password
		$pass_hashed = password_hash($post_pass, PASSWORD_BCRYPT);
		
		// Insert user into DB
		sql('INSERT INTO users (username, nickname, email, password) VALUES (?, ?, ?, ?)', ['ssss', $post_name_normalized, $post_name, $email, $pass_hashed]);
		sql('INSERT INTO user_preferences (user_id) VALUES (LAST_INSERT_ID())');
		
		// Automatically login after registration.
		$this->login($post_name, $post_pass);
		
		return true;
	}
	
	// Function used internally to check if visitor has an active user session. Returns BOOL.
	public function is_logged_in() {
		if (!array_key_exists('session', $_COOKIE)) {
			return false;
		}
		$session = sql('SELECT expiry FROM sessions WHERE id=?', ['s', $_COOKIE['session']]);
		if($session['rows'] > 0) {
			if($session['result'][0]['expiry'] < time()) {
				sql('DELETE FROM sessions WHERE id=?', ['s', $_COOKIE['session']]);
				setcookie('session', '', time() - 3600);
				return false;
			}
			return true;
		} else {
			return false;
		}
	}
	
	// Gets info about current user. Used after checking if they are logged in with is_logged_in(). Returns SQL_ASSOC or FALSE
	public function get_current_user() {
		$stmt = sql('SELECT users.id, users.username, users.nickname, users.email, users.permission_level FROM users INNER JOIN sessions ON sessions.user_id = users.id WHERE sessions.id=?', ['s', $_COOKIE['session']]);

		if (!$stmt['result'] || $stmt['rows'] < 1) {
			return false;
		} else {
			return $stmt['result'][0];
		}
	}

	public function get_current_user_prefs() {
		$stmt = sql('SELECT * FROM user_preferences INNER JOIN sessions ON sessions.user_id = user_preferences.user_id WHERE sessions.id=?', ['s', $_COOKIE['session']]);
		
		if (!$stmt['result'] || $stmt['rows'] < 1) {
			return false;
		} else {
			return $stmt['result'][0];
		}
	}
	
	// Logs out user via wiping their session. Provies option for wiping only your session or all sessions. Returns BOOL on success/failure.
	public function logout($logout_all = false) {
		if (!array_key_exists('session', $_COOKIE)) {
			return false;
		}
		
		// Remove session from the database
		if($logout_all) {
			$user = $this->get_current_user();
			$stmt = sql('DELETE FROM sessions WHERE user_id=?', ['s', $user['id']]);
		} else {
			$stmt = sql('DELETE FROM sessions WHERE id=?', ['s', $_COOKIE['session']]);
		}
		if(!$stmt['result']) {
			return false;
		}
		
		// Remove the cookie by setting expiry time to the past
		setcookie('session', '', time() - 3600);
		
		return true;
	}
	
	// Generate and return 32 character session ID.
	private function generate_session_id() {
		$id = bin2hex(random_bytes(16));
		
		// If ID exists, get new one
		if (sql('SELECT id FROM sessions WHERE id=?', ['s', $id])['rows'] > 0) {
			$id = generate_session_id();
		}
		
		return $id;
	}
}

// PAGINATION

class Pagination {
	public $offset;
	public $increment;
	public $total;

	public function Pagination() {
		$this->offset = isset($_GET['offset']) ? $_GET['offset'] : 0;
		$this->increment = 20;
		$this->total = 0;
	}

	public function Setup($increment, $total) {
		$this->increment = $increment;
		$this->total = $total;
	}

	public function Generate() {
		if($this->total <= $this->increment) {
			return false;
		}

		// Get page count
		$pages = $this->increment === 0 ? 0 : ceil($this->total / $this->increment);
		// Replaces all "page=#" from URL query
		$normalized_query = preg_replace("/\&offset\=.+?(?=(\&|$))/", "", $_SERVER['QUERY_STRING']);

		// Begin HTML
		echo '<div class="page-actions__pagination">Page:';
				
		if($pages < 8) {
			$i = 0;
			while($i < $pages) {
				$o = $i * $this->increment;
				$i++;
				echo ' <a class="page-actions__pagination-link" href="?'.$normalized_query.'&offset='.$o.'">'.$i.'</a>';
			}
		}
		else {
			// Always displays first two and last two pages plus the closest pages to the user
			$current_page = ceil($this->offset / $this->increment) + 1;
			$pages_to_display = [1, $current_page, $pages];
			$nearby_pages = [$current_page - 2, $current_page - 1, $current_page + 1, $current_page + 2];
			foreach($nearby_pages as $p) {
				if($p > 1 && $p < $pages) {
					array_push($pages_to_display, $p);
				}
			}

			$pages_to_display = array_unique($pages_to_display, SORT_NUMERIC);
			sort($pages_to_display);
			
			$previous_page = 0;
			
			foreach($pages_to_display as $page) {
				$offset = ($page - 1) * $this->increment;

				if($page - 1 != $previous_page) {
					echo ' … ';
				}
				
				echo ' <a class="page-actions__pagination-link" href="?'.$normalized_query.'&offset='.$offset.'">'.$page.'</a>';

				$previous_page = $page;
			}
		}
		
		echo '</div>';
		// End HTML

		return true;
	}
}

function valid_name(string $str) {
	$okay = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789-_";

	foreach(str_split($str) as $c) {
		if(strpos($okay, $c) === False) {
			return False;
		}
	}
	
	return true;
}

// Set user variables because god knows I'm checking if users are logged on literally every page

$auth = new Authentication();
$has_session = $auth->is_logged_in();

if($has_session) {
	$user = $auth->get_current_user();
	$prefs = $auth->get_current_user_prefs();
	$permission_level = $user['permission_level'];
} else {
	$user = False;
	$permission_level = 0;
	$prefs = [
		'timezone' => 'UTC'
	];
}

// ACCESS LEVEL

$permission_levels_temp = sql('SELECT title, permission_level FROM permission_levels ORDER BY permission_level ASC')['result'];
$permission_levels = [];

foreach($permission_levels_temp as $perm_pair) {
	$title = $perm_pair['title'];
	$level = $perm_pair['permission_level'];
	
	$permission_levels[$title] = $level;
}



// TIME FUNCTIONS

// Valid timezones

$valid_timezones = [
	'Africa' => [
		'Africa/Abidjan',
		'Africa/Accra',
		'Africa/Addis_Ababa',
		'Africa/Algier',
		'Africa/Asmara',
		'Africa/Bamako',
		'Africa/Bangui',
		'Africa/Banju',
		'Africa/Bissau',
		'Africa/Blantyre',
		'Africa/Brazzaville',
		'Africa/Bujumbur',
		'Africa/Cairo',
		'Africa/Casablanca',
		'Africa/Ceuta',
		'Africa/Conakr',
		'Africa/Dakar',
		'Africa/Dar_es_Salaam',
		'Africa/Djibouti',
		'Africa/Doual',
		'Africa/El_Aaiun',
		'Africa/Freetown',
		'Africa/Gaborone',
		'Africa/Harar',
		'Africa/Johannesburg',
		'Africa/Juba',
		'Africa/Kampala',
		'Africa/Khartou',
		'Africa/Kigali',
		'Africa/Kinshasa',
		'Africa/Lagos',
		'Africa/Librevill',
		'Africa/Lome',
		'Africa/Luanda',
		'Africa/Lubumbashi',
		'Africa/Lusak',
		'Africa/Malabo',
		'Africa/Maputo',
		'Africa/Maseru',
		'Africa/Mbaban',
		'Africa/Mogadishu',
		'Africa/Monrovia',
		'Africa/Nairobi',
		'Africa/Ndjamen',
		'Africa/Niamey',
		'Africa/Nouakchott',
		'Africa/Ouagadougou',
		'Africa/Porto-Nov',
		'Africa/Sao_Tome',
		'Africa/Tripoli',
		'Africa/Tunis',
		'Africa/Windhoe'
	],
	'America' => [
		'America/Adak',
		'America/Anchorage',
		'America/Anguilla',
		'America/Antigua',
		'America/Araguaina',
		'America/Argentina/Buenos_Aires',
		'America/Argentina/Catamarca',
		'America/Argentina/Cordoba',
		'America/Argentina/Jujuy',
		'America/Argentina/La_Rioja',
		'America/Argentina/Mendoza',
		'America/Argentina/Rio_Gallegos',
		'America/Argentina/Salta',
		'America/Argentina/San_Juan',
		'America/Argentina/San_Luis',
		'America/Argentina/Tucuman',
		'America/Argentina/Ushuaia',
		'America/Aruba',
		'America/Asuncion',
		'America/Atikokan',
		'America/Bahia',
		'America/Bahia_Banderas',
		'America/Barbados',
		'America/Belem',
		'America/Belize',
		'America/Blanc-Sablon',
		'America/Boa_Vista',
		'America/Bogota',
		'America/Boise',
		'America/Cambridge_Bay',
		'America/Campo_Grande',
		'America/Cancun',
		'America/Caracas',
		'America/Cayenne',
		'America/Cayman',
		'America/Chicago',
		'America/Chihuahua',
		'America/Costa_Rica',
		'America/Creston',
		'America/Cuiaba',
		'America/Curacao',
		'America/Danmarkshavn',
		'America/Dawson',
		'America/Dawson_Creek',
		'America/Denver',
		'America/Detroit',
		'America/Dominica',
		'America/Edmonton',
		'America/Eirunepe',
		'America/El_Salvador',
		'America/Fort_Nelson',
		'America/Fortaleza',
		'America/Glace_Bay',
		'America/Goose_Bay',
		'America/Grand_Turk',
		'America/Grenada',
		'America/Guadeloupe',
		'America/Guatemala',
		'America/Guayaquil',
		'America/Guyana',
		'America/Halifax',
		'America/Havana',
		'America/Hermosillo',
		'America/Indiana/Indianapolis',
		'America/Indiana/Knox',
		'America/Indiana/Marengo',
		'America/Indiana/Petersburg',
		'America/Indiana/Tell_City',
		'America/Indiana/Vevay',
		'America/Indiana/Vincennes',
		'America/Indiana/Winamac',
		'America/Inuvik',
		'America/Iqaluit',
		'America/Jamaica',
		'America/Juneau',
		'America/Kentucky/Louisville',
		'America/Kentucky/Monticello',
		'America/Kralendijk',
		'America/La_Paz',
		'America/Lima',
		'America/Los_Angeles',
		'America/Lower_Princes',
		'America/Maceio',
		'America/Managua',
		'America/Manaus',
		'America/Marigot',
		'America/Martinique',
		'America/Matamoros',
		'America/Mazatlan',
		'America/Menominee',
		'America/Merida',
		'America/Metlakatla',
		'America/Mexico_City',
		'America/Miquelon',
		'America/Moncton',
		'America/Monterrey',
		'America/Montevideo',
		'America/Montserrat',
		'America/Nassau',
		'America/New_York',
		'America/Nipigon',
		'America/Nome',
		'America/Noronha',
		'America/North_Dakota/Beulah',
		'America/North_Dakota/Center',
		'America/North_Dakota/New_Salem',
		'America/Nuuk',
		'America/Ojinaga',
		'America/Panama',
		'America/Pangnirtung',
		'America/Paramaribo',
		'America/Phoenix',
		'America/Port-au-Prince',
		'America/Port_of_Spain',
		'America/Porto_Velho',
		'America/Puerto_Rico',
		'America/Punta_Arenas',
		'America/Rainy_River',
		'America/Rankin_Inlet',
		'America/Recife',
		'America/Regina',
		'America/Resolute',
		'America/Rio_Branco',
		'America/Santarem',
		'America/Santiago',
		'America/Santo_Domingo',
		'America/Sao_Paulo',
		'America/Scoresbysund',
		'America/Sitka',
		'America/St_Barthelemy',
		'America/St_Johns',
		'America/St_Kitts',
		'America/St_Lucia',
		'America/St_Thomas',
		'America/St_Vincent',
		'America/Swift_Current',
		'America/Tegucigalpa',
		'America/Thule',
		'America/Thunder_Bay',
		'America/Tijuana',
		'America/Toronto',
		'America/Tortola',
		'America/Vancouver',
		'America/Whitehorse',
		'America/Winnipeg',
		'America/Yakutat',
		'America/Yellowknife'
	],
	'Antarctica' => [
		'Antarctica/Casey',
		'Antarctica/Davis',
		'Antarctica/DumontDUrville',
		'Antarctica/Macquari',
		'Antarctica/Mawson',
		'Antarctica/McMurdo',
		'Antarctica/Palmer',
		'Antarctica/Rother',
		'Antarctica/Syowa',
		'Antarctica/Troll',
		'Antarctica/Vosto'
	],
	'Arctic' => [
		'Arctic/Longyearbyen'
	],
	'Asia' => [
		'Asia/Aden',
		'Asia/Almaty',
		'Asia/Amman',
		'Asia/Anady',
		'Asia/Aqtau',
		'Asia/Aqtobe',
		'Asia/Ashgabat',
		'Asia/Atyra',
		'Asia/Baghdad',
		'Asia/Bahrain',
		'Asia/Baku',
		'Asia/Bangko',
		'Asia/Barnaul',
		'Asia/Beirut',
		'Asia/Bishkek',
		'Asia/Brune',
		'Asia/Chita',
		'Asia/Choibalsan',
		'Asia/Colombo',
		'Asia/Damascu',
		'Asia/Dhaka',
		'Asia/Dili',
		'Asia/Dubai',
		'Asia/Dushanb',
		'Asia/Famagusta',
		'Asia/Gaza',
		'Asia/Hebron',
		'Asia/Ho_Chi_Min',
		'Asia/Hong_Kong',
		'Asia/Hovd',
		'Asia/Irkutsk',
		'Asia/Jakart',
		'Asia/Jayapura',
		'Asia/Jerusalem',
		'Asia/Kabul',
		'Asia/Kamchatk',
		'Asia/Karachi',
		'Asia/Kathmandu',
		'Asia/Khandyga',
		'Asia/Kolkat',
		'Asia/Krasnoyarsk',
		'Asia/Kuala_Lumpur',
		'Asia/Kuching',
		'Asia/Kuwai',
		'Asia/Macau',
		'Asia/Magadan',
		'Asia/Makassar',
		'Asia/Manil',
		'Asia/Muscat',
		'Asia/Nicosia',
		'Asia/Novokuznetsk',
		'Asia/Novosibirs',
		'Asia/Omsk',
		'Asia/Oral',
		'Asia/Phnom_Penh',
		'Asia/Pontiana',
		'Asia/Pyongyang',
		'Asia/Qatar',
		'Asia/Qostanay',
		'Asia/Qyzylord',
		'Asia/Riyadh',
		'Asia/Sakhalin',
		'Asia/Samarkand',
		'Asia/Seou',
		'Asia/Shanghai',
		'Asia/Singapore',
		'Asia/Srednekolymsk',
		'Asia/Taipe',
		'Asia/Tashkent',
		'Asia/Tbilisi',
		'Asia/Tehran',
		'Asia/Thimph',
		'Asia/Tokyo',
		'Asia/Tomsk',
		'Asia/Ulaanbaatar',
		'Asia/Urumq',
		'Asia/Ust-Nera',
		'Asia/Vientiane',
		'Asia/Vladivostok',
		'Asia/Yakuts',
		'Asia/Yangon',
		'Asia/Yekaterinburg',
		'Asia/Yereva'
	],
	'Atlantic' => [
		'Atlantic/Azores',
		'Atlantic/Bermuda',
		'Atlantic/Canary',
		'Atlantic/Cape_Verd',
		'Atlantic/Faroe',
		'Atlantic/Madeira',
		'Atlantic/Reykjavik',
		'Atlantic/South_Georgi',
		'Atlantic/St_Helena',
		'Atlantic/Stanle'
	],
	'Australia' => [
		'Australia/Adelaide',
		'Australia/Brisbane',
		'Australia/Broken_Hill',
		'Australia/Curri',
		'Australia/Darwin',
		'Australia/Eucla',
		'Australia/Hobart',
		'Australia/Lindema',
		'Australia/Lord_Howe',
		'Australia/Melbourne',
		'Australia/Perth',
		'Australia/Sydne'
	],
	'Europe' => [
		'Europe/Amsterdam',
		'Europe/Andorra',
		'Europe/Astrakhan',
		'Europe/Athen',
		'Europe/Belgrade',
		'Europe/Berlin',
		'Europe/Bratislava',
		'Europe/Brussel',
		'Europe/Bucharest',
		'Europe/Budapest',
		'Europe/Busingen',
		'Europe/Chisina',
		'Europe/Copenhagen',
		'Europe/Dublin',
		'Europe/Gibraltar',
		'Europe/Guernse',
		'Europe/Helsinki',
		'Europe/Isle_of_Man',
		'Europe/Istanbul',
		'Europe/Jerse',
		'Europe/Kaliningrad',
		'Europe/Kiev',
		'Europe/Kirov',
		'Europe/Lisbo',
		'Europe/Ljubljana',
		'Europe/London',
		'Europe/Luxembourg',
		'Europe/Madri',
		'Europe/Malta',
		'Europe/Mariehamn',
		'Europe/Minsk',
		'Europe/Monac',
		'Europe/Moscow',
		'Europe/Oslo',
		'Europe/Paris',
		'Europe/Podgoric',
		'Europe/Prague',
		'Europe/Riga',
		'Europe/Rome',
		'Europe/Samar',
		'Europe/San_Marino',
		'Europe/Sarajevo',
		'Europe/Saratov',
		'Europe/Simferopo',
		'Europe/Skopje',
		'Europe/Sofia',
		'Europe/Stockholm',
		'Europe/Tallin',
		'Europe/Tirane',
		'Europe/Ulyanovsk',
		'Europe/Uzhgorod',
		'Europe/Vadu',
		'Europe/Vatican',
		'Europe/Vienna',
		'Europe/Vilnius',
		'Europe/Volgogra',
		'Europe/Warsaw',
		'Europe/Zagreb',
		'Europe/Zaporozhye',
		'Europe/Zuric'
	],
	'Indian' => [
		'Indian/Antananarivo',
		'Indian/Chagos',
		'Indian/Christmas',
		'Indian/Coco',
		'Indian/Comoro',
		'Indian/Kerguelen',
		'Indian/Mahe',
		'Indian/Maldive',
		'Indian/Mauritius',
		'Indian/Mayotte',
		'Indian/Reunio'
	],
	'Pacific' => [
		'Pacific/Apia',
		'Pacific/Auckland',
		'Pacific/Bougainville',
		'Pacific/Chatham',
		'Pacific/Chuuk',
		'Pacific/Easter',
		'Pacific/Efate',
		'Pacific/Enderbury',
		'Pacific/Fakaofo',
		'Pacific/Fiji',
		'Pacific/Funafuti',
		'Pacific/Galapagos',
		'Pacific/Gambier',
		'Pacific/Guadalcanal',
		'Pacific/Guam',
		'Pacific/Honolulu',
		'Pacific/Kiritimati',
		'Pacific/Kosrae',
		'Pacific/Kwajalein',
		'Pacific/Majuro',
		'Pacific/Marquesas',
		'Pacific/Midway',
		'Pacific/Nauru',
		'Pacific/Niue',
		'Pacific/Norfolk',
		'Pacific/Noumea',
		'Pacific/Pago_Pago',
		'Pacific/Palau',
		'Pacific/Pitcairn',
		'Pacific/Pohnpei',
		'Pacific/Port_Moresby',
		'Pacific/Rarotonga',
		'Pacific/Saipan',
		'Pacific/Tahiti',
		'Pacific/Tarawa',
		'Pacific/Tongatapu',
		'Pacific/Wake',
		'Pacific/Wallis'
	],
	'Others' => [
		'UTC'
	]
];

// Get user timezone
function utc_date_to_user($utc, $hour = true) {
	global $prefs;
	$timezone_utc = new DateTimeZone('UTC');
	$timezone = new DateTimeZone($prefs['timezone']);
	$date = new DateTime($utc, $timezone_utc);
	$date->setTimezone($timezone);
	if($hour) {
		$format = 'Y-m-d H:i:s O';
	} else {
		$format = 'Y-m-d O';
	}
	return $date->format($format);
}


// Returns inputted date in a user-readable format i.e. 2 hours ago, 3 months ago
function readable_date($date, $suffix = true, $verbose = false) {
	$now = new DateTime;
	$then = new DateTime($date);
	$diff = $now->diff($then);
	
	$diff->w = floor($diff->d / 7);
    $diff->d -= $diff->w * 7;
	
	$string = [
        'y' => 'year',
        'm' => 'month',
        'w' => 'week',
        'd' => 'day',
        'h' => 'hour',
        'i' => 'minute',
        's' => 'second',
    ];
	
    foreach ($string as $k => &$v) {
        if ($diff->$k) {
            $v = $diff->$k . ' ' . $v . ($diff->$k > 1 ? 's' : '');
        } else {
            unset($string[$k]);
        }
    }
	
    if(!$verbose) $string = array_slice($string, 0, 1);
	if(!$suffix) {
		return $string ? implode(', ', $string) : 'just now';
	}
    return $string ? implode(', ', $string) . ' ago' : 'just now';
}



// ACTIVITY

$activity_types = [
	'unspecified' => 0,
	'current' => 1,
	'completed' => 2,
	'paused' => 3,
	'dropped' => 4,
	'planned' => 5,
	'post' => 10
];



// DATABASE FUNCTIONS

// Passes database a statement and returns result.
// 
// Example uses:
// sql('SELECT * FROM forum');
// sql('SELECT * FROM forum WHERE id=? AND title=?', ['is', $forum_id, $forum_title]);
// sql('SELECT * FROM forum WHERE id=1 AND title="Hello world"', false, ['assoc' => false]);
// 
// Returns an array:
//     result -> false if failed, null if no change, true or a result if successfull
//     response_code -> string to describe outcome, used in notices
//     response_type -> string to describe type of outcome, used in notices
//     rows -> number of affected/returned rows
function sql(string $stmt, $params = false, $options = false) {
	global $db;
	$dbfail = [
			'result' => false,
			'response_code' => 'database_failure',
			'response_type' => 'error',
			'rows' => -1
		];
	
	// Execute statement
	if(!$q = $db->prepare($stmt)) { return $dbfail; }
	if($params !== false) { $q->bind_param(...$params); }
	if(!$q->execute()) { return $dbfail; }

	// SELECT
	if(strpos(trim($stmt), 'SELECT') === 0) {
		$res = $q->get_result();
		$rows = $res->num_rows;
		if($rows < 1) {
			$res = true;
		} elseif($options['assoc'] === false) {
			$res = $res->fetch_all();
		} else {
			$res = $res->fetch_all(MYSQLI_ASSOC);
		}
	}
	
	// UPDATE && DELETE
	elseif(strpos(trim($stmt), 'UPDATE') === 0 || strpos(trim($stmt), 'DELETE') === 0) {
		$rows = $q->affected_rows;
		if($rows < 1) {
			return [
					'result' => null,
					'response_code' => 'database_null_commit',
					'response_type' => 'error',
					'rows' => $rows
				];
		} else {
			$res = true;
		}
	}

	// INSERT
	else {
		$rows = $q->affected_rows;
		$res = true;
	}

	$q->close();

	// Return result
	return [
			'result' => $res,
			'response_code' => 'success',
			'response_type' => 'generic',
			'rows' => $rows
		];
}



// For use on user POST pages. Closes relevant pieces and redirects user to a page.
function finalize(string $page = '/', ...$input_notices) {
	global $db;
	$db->close();

	if(isset($input_notices)) {
		$output_notices = [];

		$i = 0;
		foreach($input_notices as $n) {
			$output_notices[$i]['case'] = $n[0];

			if(!isset($n[1])) {
				$output_notices[$i]['type'] = 'generic';
			} else {
				$output_notices[$i]['type'] = $n[1];
			}

			if(!isset($n[2])) {
				$output_notices[$i]['details'] = '';
			} else {
				$output_notices[$i]['details'] = $n[2];
			}
			$i++;
		}

		$_SESSION['notice'] = $output_notices;
	}
	
	header('Location: '.$page);
	exit();
}


// COLLECTION FUNCTIONS

$valid_coll_types = ['video', 'game', 'literature', 'other'];

$valid_status = ['current', 'completed', 'paused', 'dropped', 'planned', 'other'];

// Input a score from a certain rating system and convert to 100-scale
function score_normalize(int $score, int $system) {
	switch($system) {
		case 3:
			return $score * 30;
		case 5:
			return $score * 20;
		case 10:
			return $score * 10;
		case 20:
			return $score * 5;
		case 100:
			return $score;
		default:
			return 0;
	}
}

// Input a score from a 100-scale to a certain rating system
function score_extrapolate(int $score, int $system) {
	switch($system) {
		case 3:
			return to_int($score / 30);
		case 5:
			return to_int($score / 20);
		case 10:
			return to_int($score / 10);
		case 20:
			return to_int($score / 5);
		case 100:
			return to_int($score);
		default:
			return 0;
	}
}


?>