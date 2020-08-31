<?php

// GLOBAL VARIABLES

date_default_timezone_set('UTC');

define("FILEPATH", "/");
include("keyring.php");
// keys.php contains potentially sensitive information such as the MYSQL_HOST/USER/PASS/DB/PORT variables.

$db = new mysqli(MYSQL_HOST, MYSQL_USER, MYSQL_PASS, MYSQL_DB, MYSQL_PORT);



// GENERIC FUNCTIONS

function to_int($s) {
	return intval(round($s));
}



// AUTH SYSTEM

class Authentication {
	private $db;
	
	public function Authentication() {
        $this->db = $GLOBALS['db']; // This is terrible. Don't do this.
	}
	
	// Function called by user attempting login. Returns BOOL for success/fail.
	public function login(string $user, string $pass) {
		// Set expiry date in Unix time for use in database and cookies
		$offset = 90 * 24 * 60 * 60; // days * hours * minutes * seconds
		$expiry = time() + $offset;
		
		// Check user exists. Username is case insensitive.
		$stmt = $this->db->prepare("SELECT id, username, password FROM users WHERE username=?");
		$normalized_user = strtolower($user);
		$stmt->bind_param("s", $normalized_user);
		$stmt->execute();
		$res = $stmt->get_result();
		$stmt->free_result();
		
		if ($res->num_rows === 0) {
			return false;
		}
		
		$resData = $res->fetch_assoc();
		
		// Validate password
		$valid = password_verify($pass, $resData['password']);
		if (!$valid) {
			return false;
		}
		
		// Create new user session
		$stmt = $this->db->prepare("INSERT INTO sessions (id, user_id, expiry, user_ip) VALUES (?, ?, ?, ?)");
		$id = $this->generateSessionID();
		$user_id = $resData['id'];
		$user_ip = $_SERVER['REMOTE_ADDR'];
		$stmt->bind_param("ssss", $id, $user_id, $expiry, $user_ip);
		$stmt->execute();
		
		setcookie('session', $id, $expiry);
		return true;
	}
	
	// Function called by user attempting register. Returns BOOL for success/fail.
	public function register(string $user, string $email, string $pass) {
		// Check for existing user
		$stmt = $this->db->prepare("SELECT id FROM users WHERE username=?");
		$normalized_user = strtolower($user);
		$stmt->bind_param("s", $normalized_user);
		$stmt->execute();
		$res = $stmt->get_result();
		if ($res->num_rows > 0) {
			return false;
		}
		
		// Hash password
		$hash = password_hash($pass, PASSWORD_BCRYPT);
		
		// Insert user into DB
		$stmt = $this->db->prepare("INSERT INTO users (username, nickname, email, password) VALUES (?, ?, ?, ?)");
		$stmt->bind_param("ssss", strtolower($user), $user, $email, $hash);
		$stmt->execute();
		
		// Setup basic user preferences
		$stmt = $this->db->prepare("INSERT INTO user_preferences (user_id) VALUES (LAST_INSERT_ID())");
		$stmt->execute();
		
		$stmt->close();
		
		// Automatically login after registration.
		$this->login($user, $pass);
		
		return true;
	}
	
	// Function used internally to check if visitor has an active user session. Returns BOOL.
	public function isLoggedIn() {
		if (!array_key_exists('session', $_COOKIE)) {
			return false;
		}
		
		$que = sqli_result_bindvar("SELECT expiry FROM sessions WHERE id=?", "s", $_COOKIE['session']);
		$res = $que->fetch_assoc();
		if($que->num_rows > 0) {
			if($res["expiry"] < time()) {
				sqli_execute("DELETE FROM sessions WHERE id=?", "s", $_COOKIE['session']);
				setcookie('session', '', time() - 3600);
				return false;
			}
			return true;
		} else {
			return false;
		}
	}
	
	// Gets info about current user. Used after checking if they are logged in with isLoggedIn(). Returns SQL_ASSOC or FALSE
	public function getCurrentUser() {
		// INNER JOIN pulls data from the users table where the ID matches
		$stmt = $this->db->prepare("SELECT users.id, users.username, users.nickname, users.email, users.permission_level FROM users INNER JOIN sessions ON sessions.user_id = users.id WHERE sessions.id=?");
		$stmt->bind_param("s", $_COOKIE['session']);
		$stmt->execute();
		$res = $stmt->get_result();
		
		if ($res->num_rows > 0) {
			return $res->fetch_assoc();
		} else {
			return false;
		}
	}

	public function getCurrentUserPrefs() {
		// INNER JOIN pulls data from the users table where the ID matches
		$stmt = $this->db->prepare("SELECT * FROM user_preferences INNER JOIN sessions ON sessions.user_id = user_preferences.user_id WHERE sessions.id=?");
		$stmt->bind_param("s", $_COOKIE['session']);
		$stmt->execute();
		$res = $stmt->get_result();
		
		if ($res->num_rows > 0) {
			return $res->fetch_assoc();
		} else {
			return false;
		}
	}
	
	// Logs out user via wiping their session. Returns BOOL on success/failure.
	public function logout() {
		if (!array_key_exists('session', $_COOKIE)) {
			return false;
		}
		
		// Remove session from the database
		$stmt = $this->db->prepare("DELETE FROM sessions WHERE id=?");
		$stmt->bind_param("s", $_COOKIE['session']);
		$stmt->execute();
		
		// Remove the cookie by setting expiry time to the past
		setcookie('session', '', time() - 3600);
		
		return true;
	}
	
	// Generate and return 32 character session ID.
	private function generateSessionID() {
		$id = bin2hex(random_bytes(16));
		
		// If ID exists, get new one
		if (sqli_result_bindvar("SELECT id FROM sessions WHERE id=?", "s", $id)->num_rows > 0) {
			$id = generateSessionID();
		}
		
		return $id;
	}
}

// Set user variables because god knows I'm checking if users are logged on literally every page

$auth = new Authentication();
$has_session = $auth->isLoggedIn();

if ($has_session) {
	$user = $auth->getCurrentUser();
	$prefs = $auth->getCurrentUserPrefs();
	$permission_level = $user['permission_level'];
} else {
	$permission_level = 0;
	$prefs = [
		'timezone' => 'UTC'
	];
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
function utc_date_to_user($utc) {
	$prefs = $GLOBALS['prefs'];
	$timezone_utc = new DateTimeZone('UTC');
	$timezone = new DateTimeZone($prefs['timezone']);
	$date = new DateTime($utc, $timezone_utc);
	$date->setTimezone($timezone);
	return $date->format('Y-m-d H:i:s O');
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



// DATABASE FUNCTIONS

// Passes database an SQL statement and returns result.
function sqli_result(string $sql) {
	$db = $GLOBALS['db'];
	
	$q = $db->prepare($sql);
	$q->execute();
	$r = $q->get_result();
	$q->close();
	return $r;
}

// Passes database an SQL statement with sanitized variable and returns result.
function sqli_result_bindvar(string $sql, string $insert_type, string $insert_variable) {
	$db = $GLOBALS['db'];
	
	$q = $db->prepare($sql);
	$q->bind_param($insert_type, $insert_variable);
	$q->execute();
	$r = $q->get_result();
	$q->close();
	return $r;
}

// Executes an SQL statement. Returns TRUE for success and STRING for error.
function sqli_execute(string $sql, string $insert_type, string $insert_variable) {
	$db = $GLOBALS['db'];
	
	$q = $db->prepare($sql);
	$q->bind_param($insert_type, $insert_variable);
	$q->execute();
	$error = $q->error;
	$q->close();
	if($error !== "") {
		return $error;
	}
	return true;
}

// For use on user POST pages. Closes relevant pieces and redirects user to a page.
function finalize(string $return_to = '/') {
	$db = $GLOBALS['db'];
	
	$db->close();
	header('Location: '.$return_to);
	exit();
}


// COLLECTIOn FUNCTIONS

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