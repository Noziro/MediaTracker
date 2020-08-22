<?php

// GLOBAL VARIABLES

date_default_timezone_set('UTC');

define("FILEPATH", "/");
include("keyring.php");
// keys.php contains potentially sensitive information such as the MYSQL_HOST/USER/PASS/DB/PORT variables.

$db = new mysqli(MYSQL_HOST, MYSQL_USER, MYSQL_PASS, MYSQL_DB, MYSQL_PORT);



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
		$stmt = $this->db->prepare("INSERT INTO sessions (id, user_id, expiry) VALUES (?, ?, ?)");
		$id = $this->generateSessionID();
		$user_id = $resData['id'];
		$stmt->bind_param("sss", $id, $user_id, $expiry);
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



// TIME FUNCTIONS

// Set user timezone
// WIP Disabled - currently throws an error on timezone set.
//$auth = new Authentication;
//
//if($auth->isLoggedIn()) {
//	$user = $auth->getCurrentUser();
//	$user_timezone = sqli_result_bindvar('SELECT timezone FROM user_preferences WHERE user_id=?', 's', $user['id']);
//	$user_timezone = $user_timezone->fetch_row()[0];
//	
//	date_default_timezone_set($user_timezone);
//}

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


// OTHER FUNCTIONS




?>