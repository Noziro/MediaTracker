<?php declare(strict_types=1);

session_start();

define("PATH", $_SERVER["DOCUMENT_ROOT"] . "/");
# the URL as displayed in the client browser
DEFINE("URL", [
	'PATH_STRING' => strtok($_SERVER["REQUEST_URI"], '?'),
	'PATH_ARRAY' => remove_empties(explode('/', strtok($_SERVER["REQUEST_URI"], '?')))
]);
define("SITE_NAME", "MediaTracker");
define("SQL_CREDENTIALS", [
	'host' => getenv('DB_HOST') ?: 'localhost',
	'user' => getenv('DB_USER') ?: 'mediatracker',
	'pass' => getenv('DB_PASSWORD') ?: 'change_me',
	'dbname' => getenv('DB_DATABASE') ?: 'mediatracker',
	'port' => getenv('DB_PORT') ? intval(getenv('DB_PORT')) : 3306
]);

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$pl_timer_start = hrtime(True);

// GLOBAL VARIABLES

date_default_timezone_set('UTC');

const VALID_STATUS = ['current', 'completed', 'paused', 'dropped', 'planned', 'other'];
const VALID_MEDIA_TYPES = ['video', 'game', 'literature', 'other'];
const VALID_ACTIVITY_TYPES = [
	'unspecified' => 0,
	'current' => 1,
	'completed' => 2,
	'paused' => 3,
	'dropped' => 4,
	'planned' => 5,
	'post' => 10
];
const VALID_TIMEZONES = [
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

try {
	# the @ suppresses error output, you may want to remove this for debugging
	$db = @ new mysqli(...array_values(SQL_CREDENTIALS));
}
catch( mysqli_sql_exception $e ){
	http_response_code(503);
	exit();
}


$result = $db->query("SHOW TABLES");
if( !$result || !$result->fetch_assoc() ){
	require_once PATH.'server/schema.php';
}

// TODO - this should be cleaned up or deleted
#set_error_handler("errorHandler");
#function errorHandler($errno, $errstr, $errfile, $errline)
#{
#    error_log("$errstr in $errfile:$errline");
#	echo $errstr.'<br><br>';
#    #header('Location: /500');
#}



// GENERIC FUNCTIONS

function pprint_internal( $var ){
	echo '('.gettype($var).') ';
	if( gettype($var) === 'string' ){
		echo $var;
	}
	elseif( gettype($var) === 'array' ){
		echo '[<div style="padding-left: 16px;">';
		foreach( $var as $key => $subvar ){
			echo '('.gettype($key).') ' . $key . ' => ';
			pprint_internal($subvar);
			echo '<br />';
		}
		echo '</div>]';
	}
	elseif( gettype($var) === 'boolean' ){
		echo $var ? 'true' : 'false';
	}
	elseif( gettype($var) === 'integer' ){
		echo $var;
	}
	else {
		echo '<pre>';
		var_dump($var);
		echo '</pre>';
	}
}

function pprint( ...$mixed ){
	foreach( $mixed as $var ){
		echo '<div style="background: black; color: yellow; padding: 5px; margin: 5px;">';
		pprint_internal($var);
		echo '</div>';
	}
}

function to_int($s) {
	return intval(round($s));
}

// Formats text that is retrieved from SQL database.
function format_user_text($s) {
	// encode BB here
	return nl2br(htmlspecialchars($s));
}

// Gets the last element of the array, starting from index 1 (last)
function nth_last( array $arr, int $nth = 1 ){
	return $arr[count($arr)-$nth];
}

// This function purely exists because the builtin array_filter() does not re-number array indexes
function remove_empties( array $arr ): array {
	$filtered_arr = [];
	foreach( $arr as $item ){
		if( trim($item) === '' || $item === null ){
			continue;
		}
		$filtered_arr[] = $item;
	}
	return $filtered_arr;
}

// Returns true if a regular expression would match the provided string, false if not.
function preg_eval( string $pattern, string $string ): bool {
	$matches = [];
	preg_match($pattern, $string, $matches);
	if( count($matches) > 0 ){
		return true;
	}
	return false;
}

// simple function to simplify adding a data-autofill text for a variable that has an unknown value
function autofill_if_set( $mixed ): string {
	if( $mixed !== null && strlen((string) $mixed) > 0 ){
		echo ' data-autofill="'.$mixed.'" ';
	}
	return '';
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
		if( !$stmt->ok || $stmt->row_count < 1 ){
			return false;
		}
		
		$user_data = $stmt->rows[0];
		
		// Validate password
		$valid = password_verify($post_pass, $user_data['password']);
		if( !$valid ){
			return false;
		}

		// Set expiry date in Unix time for use in database and cookies
		$offset = 90 * 24 * 60 * 60; // days * hours * minutes * seconds
		$expiry = time() + $offset;
		
		// Set other variables
		$session = $this->generate_session_id();
		$user_ip = json_encode([$_SERVER['REMOTE_ADDR']]);

		// Create new user session
		$stmt = sql('INSERT INTO sessions (id, user_id, expiry, user_ip) VALUES (?, ?, ?, ?)', ['siis', $session, $user_data['id'], $expiry, $user_ip]);
		if( !$stmt->ok ){
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
		if( !$stmt->ok || $stmt->row_count > 0 ){
			return false;
		}
		
		// Hash password
		$pass_hashed = password_hash($post_pass, PASSWORD_BCRYPT);
		
		// Insert user into DB
		sql('INSERT INTO users (username, nickname, email, password) VALUES (?, ?, ?, ?)', ['ssss', $post_name_normalized, $post_name, $email, $pass_hashed]);
		
		// Automatically login after registration.
		$this->login($post_name, $post_pass);
		
		return true;
	}
	
	// Function used internally to check if visitor has an active user session. Returns BOOL.
	public function is_logged_in() {
		if( !array_key_exists('session', $_COOKIE) ){
			return false;
		}
		$session = sql('SELECT user_ip, expiry FROM sessions WHERE id=?', ['s', $_COOKIE['session']]);
		if( $session->row_count > 0 ){
			// Delete session if it has expired
			if( $session->rows[0]['expiry'] < time() ){
				sql('DELETE FROM sessions WHERE id=?', ['s', $_COOKIE['session']]);
				setcookie('session', '', time() - 3600);
				return false;
			}
			// Log user IP if different than before, for security purposes
			$user_ips = (array) json_decode($session->rows[0]['user_ip']);
			if( !in_array($_SERVER['REMOTE_ADDR'], $user_ips, false) ){
				array_push($user_ips, $_SERVER['REMOTE_ADDR']);
				sql('UPDATE sessions SET user_ip=? WHERE id=?', ['ss', json_encode($user_ips), $_COOKIE['session']]);
			}
			return true;
		} else {
			return false;
		}
	}
	
	// Gets info about current user. Used after checking if they are logged in with is_logged_in(). Returns SQL_ASSOC or FALSE
	public function get_current_user() {
		$stmt = sql('SELECT users.id, users.username, users.nickname, users.email, users.permission_level, users.profile_colour, users.timezone FROM users INNER JOIN sessions ON sessions.user_id = users.id WHERE sessions.id=?', ['s', $_COOKIE['session']]);

		if( !$stmt->ok || $stmt->row_count < 1 ){
			return false;
		} else {
			return $stmt->rows[0];
		}
	}
	
	// Logs out user via wiping their session. Provies option for wiping only your session or all sessions. Returns BOOL on success/failure.
	public function logout($logout_all = false) {
		if( !array_key_exists('session', $_COOKIE) ){
			return false;
		}
		
		// Remove session from the database
		if( $logout_all ){
			$user = $this->get_current_user();
			$stmt = sql('DELETE FROM sessions WHERE user_id=?', ['s', $user['id']]);
		} else {
			$stmt = sql('DELETE FROM sessions WHERE id=?', ['s', $_COOKIE['session']]);
		}
		if( !$stmt->ok ){
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
		if( sql('SELECT id FROM sessions WHERE id=?', ['s', $id])->row_count > 0 ){
			$id = generate_session_id();
		}
		
		return $id;
	}
}	

function valid_name(string $str) {
	$okay = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789-_";

	foreach( str_split($str) as $c ){
		if( strpos($okay, $c) === False ){
			return False;
		}
	}
	
	return true;
}

// Set user variables because god knows I'm checking if users are logged on literally every page

$auth = new Authentication();
$has_session = $auth->is_logged_in();
if( $has_session ){
	$user = $auth->get_current_user();
	$permission_level = $user['permission_level'];
} else {
	$user = ['id' => -1];
	$permission_level = 0;
}
if( !isset($user['timezone']) ){
	$user['timezone'] = 'UTC';
}

// ACCESS LEVEL

$stmt = sql('SELECT title, permission_level FROM permission_levels ORDER BY permission_level ASC');
$permission_levels = [];

if( $stmt->row_count > 0 ){	
	foreach( $stmt->rows as $perm_pair ){
		$title = $perm_pair['title'];
		$level = $perm_pair['permission_level'];
		
		$permission_levels[$title] = $level;
	}
}



// TIME FUNCTIONS

// Get a date formatted to the user's preferred timezone
function utc_date_to_user( string $utc, bool $hour = true ): string {
	global $user;
	$preferred_timezone = isset($user['timezone']) ? $user['timezone'] : 'UTC';
	$timezone_utc = new DateTimeZone('UTC');
	$timezone = new DateTimeZone($preferred_timezone);
	$date = new DateTime($utc, $timezone_utc);
	$date->setTimezone($timezone);
	if( $hour ){
		$format = 'Y-m-d H:i:s O';
	} else {
		$format = 'Y-m-d O';
	}
	return $date->format($format);
}


// Returns inputted date in a user-readable format i.e. 2 hours ago, 3 months ago
function readable_date( string $date, bool $suffix = true, bool $verbose = false ): string {
	$now = new DateTime;
	$then = new DateTime($date);
	$diff = $now->diff($then);
	
	$weeks = floor($diff->d / 7);
	$diff->d -= $weeks * 7;
	
	$string = [
        'y' => 'year',
        'm' => 'month',
		// 'w' => 'week', // Removed as weeks are calculated separately
        'd' => 'day',
        'h' => 'hour',
        'i' => 'minute',
        's' => 'second',
    ];
	
	foreach( $string as $k => &$v ){
		$value = $k === 'w' ? $weeks : $diff->$k;
		if( $value ){
			$v = $value . ' ' . $v . ($value > 1 ? 's' : '');
		} else {
			unset($string[$k]);
		}
	}
	
    if(!$verbose) $string = array_slice($string, 0, 1);
	if( !$suffix ){
		return $string ? implode(', ', $string) : 'just now';
	}
    return $string ? implode(', ', $string) . ' ago' : 'just now';
}



// DATABASE FUNCTIONS

class SqlResult {
	public bool $ok;
	public array $rows;
	public string $response_code;
	public string $response_type;
	public int $row_count;
	private array $valid_response_codes = [
		'database_failure' => 'error',
		'database_null_commit' => 'error',
		'success' => 'generic'
	];

	public function __construct(bool $ok, string $response_code, array $rows = [], int $row_count = -999) {
		$this->ok = $ok;
		if( array_key_exists($response_code, $this->valid_response_codes) ){
			$this->response_code = $response_code;
			$this->response_type = $this->valid_response_codes[$response_code];
		} else {
			throw new ValueError("Invalid response code: $response_code");
		}
		$this->rows = $rows;
		$this->row_count =
			$row_count === -999 ?
				$this->ok ?
					count($result)
					: -1
				: $row_count;
	}
}

// Passes database a statement and returns result.
// 
// Example uses:
// sql('SELECT * FROM forum');
// sql('SELECT * FROM forum WHERE id=? AND title=?', ['is', $forum_id, $forum_title]);
// sql('SELECT * FROM forum WHERE id=1 AND title="Hello world"', false, false);
// 
// Returns an array:
//     result -> false if failed, null if no change, true or a result if successfull
//     response_code -> string to describe outcome, used in notices
//     response_type -> string to describe type of outcome, used in notices
//     rows -> number of affected/returned rows
function sql( string $stmt, array $params = [], bool $associate_names = true ){
	global $db;
	$rows = [];
	$row_count = -999;

	# ignore value if only types are passed, as this is an obviously faulty param array.
	$params = count($params) < 2 ? [] : $params;
	
	// Execute statement
	if( !$q = $db->prepare($stmt) ){
		return new SqlResult(false, 'database_failure');
	}
	if( count($params) > 0 ){
		$q->bind_param(...$params);
	}
	if( !$q->execute() ){
		return new SqlResult(false, 'database_failure');
	}

	// SELECT
	if( strpos(trim($stmt), 'SELECT') === 0 ){
		$res = $q->get_result();
		$row_count = $res->num_rows;
		$rows = $associate_names === true ? $res->fetch_all(MYSQLI_ASSOC) : $res->fetch_all();
	}
	
	// UPDATE && DELETE
	elseif( strpos(trim($stmt), 'UPDATE') === 0 || strpos(trim($stmt), 'DELETE') === 0 ){
		$row_count = $q->affected_rows;
		if( $row_count < 1 ){
			return new SqlResult(false, 'database_null_commit');
		}
	}

	// INSERT
	else {
		$row_count = $q->affected_rows;
	}

	$q->close();

	// Return result
	return new SqlResult(true, 'success', $rows, $row_count);
}


// User Notices

class Notice {
	static array $valid_codes = [
		// Successes
		'login_success' => [
			'type' => 'success',
			'message' => 'Successfully logged into your account. Welcome back!'
		],
		'login_success' => [
			'type' => 'success',
			'message' => "Successfully logged into your account. Welcome back!"
		],
		'register_success' => [
			'type' => 'success',
			'message' => "Successfully created your account. Have fun!"
		],
		'logout_success' => [
			'type' => 'success',
			'message' => "Successfully logged out of your account. Thanks for visiting."
		],
		'success' => [
			'type' => 'success',
			'message' => "Action performed successfully."
		],
		'partial_success' => [
			'type' => 'success',
			'message' => "Action partially succeeded - some updates failed. See below for details."
		],
		
		// Errors
		'required_field' => [
			'type' => 'error',
			'message' => "Please fill out the required fields."
		],
		'login_bad' => [
			'type' => 'error',
			'message' => "Incorrect login credentials. Please try again."
		],
		'register_exists' => [
			'type' => 'error',
			'message' => "User already exists."
		],
		'register_match' => [
			'type' => 'error',
			'message' => "Passwords do not match."
		],
		'invalid_name' => [
			'type' => 'error',
			'message' => "Name contains invalid characters."
		],
		'invalid_pass' => [
			'type' => 'error',
			'message' => "Password does not meet requirements."
		],
		'logout_failure' => [
			'type' => 'error',
			'message' => "Failed to log you out. Please try again or report the error to the admins."
		],
		'require_sign_in' => [
			'type' => 'error',
			'message' => "Please sign in before attempting this action."
		],
		'database_failure' => [
			'type' => 'error',
			'message' => "An error occured in the server database while performing your request."
		],
		'database_null_commit' => [
			'type' => 'error',
			'message' => "An attempted database commit did not result in an action."
		],
		'disallowed_action' => [
			'type' => 'error',
			'message' => "Attempted to perform an invalid or unrecognized action."
		],
		'incorrect_method' => [
			'type' => 'error',
			'message' => "Page was accessed by the wrong method. Try switching from POST to GET or vice versa."
		],
		'unauthorized' => [
			'type' => 'error',
			'message' => "Attempted operation outside of user authority."
		],
		'invalid_value' => [
			'type' => 'error',
			'message' => "A value you entered was invalid or out of expected bounds. Please try again."
		],
		'image_invalid' => [
			'type' => 'error',
			'message' => "Uploaded file is invalid."
		],
		'image_failure' => [
			'type' => 'error',
			'message' => "Something went wrong while uploading your image."
		],
		'file_system_insufficient_permissions' => [
			'type' => 'error',
			'message' => "The server has insufficient file system permissions to perform this action.",
			'details' => 'The admin must make changes to the app environment to resolve this error.'
		],
		'file_copy_failure' => [
			'type' => 'error',
			'message' => "Failed to copy a local file."
		],
		'must_complete_prior' => [
			'type' => 'generic',
			'message' => "This action cannot be taken until you complete a prior action."
		],

		// Neutral
		'no_change_detected' => [
			'type' => 'generic',
			'message' => "No changes were applied, as none were detected."
		],
		'blank' => [
			'type' => 'generic',
			'message' => ''
		],
		'unimplemented' => [
			'type' => 'generic',
			'message' => "This feature has yet to be implemented."
		],
		'default' => [
			'type' => 'generic',
			'message' => "This was meant to say something, but it doesn't!"
		]
	];

	static function from_mixed( string|array $mixed ){
		if( gettype($mixed) === 'string' ){
			return new Notice($mixed);
		}
		if( gettype($mixed) === 'array' ){
			return new Notice($mixed[0], $mixed[1] ?? '');
		}
	}

	public string $code;
	public string $type;
	public string $message;
	public string $details;

	public function __construct( string $code = 'default', string $details = '' ){
		$this->details = $details;
		if( array_key_exists($code, Notice::$valid_codes) ){
			$this->code = $code;
			$this->type = Notice::$valid_codes[$code]['type'];
			$this->message = Notice::$valid_codes[$code]['message'];
			$this->details = $details ?? Notice::$valid_codes[$code]['details'] ?? '';
		} else {
			throw new ValueError("Invalid notice code: $response_code");
		}
	}
}


// Immediately ceases page exection, closes relevant pieces, and redirects user to a page of choice.
function bailout( string $page = '/', string|array|Notice ...$input_notices ){
	global $db;
	$db->close();

	if( isset($input_notices) ){
		$output_notices = array_map(
			fn($notice): Notice => $notice instanceof Notice
				? $notice
				: Notice::from_mixed($notice)
			, $input_notices );

		$_SESSION['notice'] = $output_notices;
	}
	
	header('Location: '.$page);
	exit();
}

// Loads an error page while retaining the same URL. Useful for impermanent status codes where the resource does actually exist.
function soft_error( int $error = 404 ){
	# checks which ob level we are in and runs appropriate code.
	# the first level is activated as soon as possible and incudes the header and footer
	# the second level is activated in index.php right before loading the views/* files
	# find "ob_start()" in the index to see for yourself
	global $file;
	$file = 'error';
	if( ob_get_level() > 1 ){
		ob_clean();
		http_response_code($error);
	}
	else { # ob_get_level() === 0
		throw new Exception("soft_error() called without output buffering active.");
	}
}


// COLLECTION FUNCTIONS

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