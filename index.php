<?php
# Temp until site name is decided
$website = "Collections.com";



# REQUIRED SETUP

# Defines basic PATH for easy use across both client & server
define("PATH", $_SERVER["DOCUMENT_ROOT"] . "/");

# Includes various important variables and functions
include(PATH . "server/server.php");



# INDEX-SPECIFIC SETUP

# Strips GET variables off the end and returns only the base URL
$url = strtok($_SERVER["REQUEST_URI"], '?');

# Check for various conditions related to the URL and make usable later in code
if($url == '/') {
	#index
	$url = 'index';
} elseif(mysqli_connect_errno()) {
	#500
	http_response_code(500);
	$url = '500';
} elseif(file_exists("views/$url.php") != 1) {
	#404
	header('Location: /404');
} else {
	#generic pages - strips the / off the beginning
	$url = substr($url, 1);
}

$url_split = explode('/', $url);
$url_readable = end($url_split);



# USER AUTH

$auth = new Authentication();

$has_session = $auth->isLoggedIn();
if ($has_session) {
	$user = $auth->getCurrentUser();
}



# User Login

#Temporary 
#$logged_in = False;

#$userQ = mysqli_query($db, "SELECT id, username FROM users WHERE id=1");
#if($userQ !== null) {
#	$logged_in = True;
#	$user = mysqli_fetch_assoc($userQ);
#	$user_id = $user['id'];
#	$username = $user['username'];
#}

?>

<!DOCTYPE HTML>
<html lang="en" class="theme-dark">
	<head>
		<title><?php
			echo $website . " - ";
			if($url == "index") {
				echo "Track Your Collections!";
			} else {
				echo ucfirst($url_readable);
			}
		?></title>
		<meta charset="utf-8">
		<meta name="description" content="Placeholder">
		<meta name="keywords" content="Placeholder">
		<link rel="icon" type="image/png" href="<?=FILEPATH?>static/img/favicon.png">
		<link rel="icon" type="image/png" href="<?=FILEPATH?>static/img/favicon-32.png" sizes="32x32">
		<link rel="icon" type="image/png" href="<?=FILEPATH?>static/img/favicon-256.png" sizes="256x256">
		
		<link rel="stylesheet" href="<?=FILEPATH?>static/css/style.css">
		
		<?php if (file_exists(PATH."static/css/".$url_readable.".css")) : ?>
		<link rel="stylesheet" href="<?=FILEPATH."static/css/".$url?>.css">
		<?php endif ?>
		
		<?php if (file_exists(PATH."static/js/".$url_readable.".js")) : ?>
		<script type="text/javascript" src="<?=FILEPATH."static/js/".$url_readable.".js"?>">
		<?php endif ?>
		
		<!--<script type="text/javascript" src="<?=FILEPATH?>static/js/jquery-3.3.1.min.js" async></script>-->
		<script type="text/javascript" src="<?=FILEPATH?>static/js/scripts.js" defer></script>
		
		<link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Montserrat:400,400i,700,700i">
		
		<!-- Theme Selector -->
		<script>
			function setTheme(theme) {
				var classes = document.documentElement.classList;
				
				for (var cls of classes) {
					if (cls.startsWith('theme-')) {
						classes.remove(cls);
					}
				}
				
				classes.add('theme-' + theme);
			}
			
			if(localStorage.getItem('theme') !== null) {
				setTheme(localstorage.getItem('theme'));
			}
		</script>
	</head>
	<body class="<?php
	
	if ($url == "404") {
		echo "error";
	} else {
		echo implode(" ", $url_split);
	}
	?>">
		<nav id="nav" class="wrapper">
			<div class="container">
				<a class="site-nav identity" href="<?=FILEPATH?>">
					<!--<img class="logo" src="<?=FILEPATH?>static/img/logo.png" alt="website logo">-->
					<span class="name"><?=$website?></span>
				</a>
				
				<div class="site-nav primary list horizontal">
					<a class="item text" href="<?=FILEPATH?>database">Database</a>
					<a class="item text" href="<?=FILEPATH?>forum">Forum</a>
					<a class="item text" href="<?=FILEPATH?>discuss">Discuss</a>
				</div>
				
				<div class="site-nav search">
					<input type="search" autocomplete="off" class="search-bar" placeholder="Search for Movies, Games, TV, Books, Anime, and more...">
				</div>
				
				<div class="site-nav user list horizontal">
					<?php if($has_session) : ?>
					
					<a class="item text" href="<?=FILEPATH?>collection">Collection</a>
					
					<div class="item dropdown">
						<a class="item text user" href="<?=FILEPATH . "user?id=" . $user["id"]?>"><?=$user["nickname"]?></a>
						
						<div class="dropdown-menu list vertical">
							<a class="item text" href="<?=FILEPATH?>account/settings">Settings</a>
							<form action="/session" method="POST" class="item text logout" >
								<input type="hidden" name="action" value="logout">
								<input type="submit" name="commit" value="Logout" class="link">
							</form>
						</div>
					</div>
						
					<?php else : ?>
					
					<a class="item text login" href="<?=FILEPATH?>login?action=login">Login</a>
					<a class="item text register" href="<?=FILEPATH?>login?action=register">Register</a>
					
					<?php endif ?>
				</div>
			</div>
		</nav>
		<div class="js-scroll" style="visibility:hidden;"></div>
		
		<?php if(isset($_GET['notice'])) : ?>
		
		<div class="wrapper notice">
			<div class="container center-text">
				<p>
					<?php
					switch($_GET['notice']) {
						case 'login-success':
							echo "Successfully logged into your account. Welcome back!";
							break;
						case 'register-success':
							echo "Successfully created your account. Have fun!";
							break;
						case 'logout-success':
							echo "Successfully logged out of your account. Thanks for visiting.";
							break;
						default:
							echo "This was meant to say something, but it doesn't! Our bad.";
							break;
					}
					?>
				</p>
			</div>
		</div>
		
		<?php endif ?>
		
		<?php if(isset($_GET['error'])) : ?>
		
		<div class="wrapper notice error">
			<div class="container center-text">
				<p>
					<?php
					switch($_GET['error']) {
						case 'required-field':
							echo "Please fill out the required fields.";
							break;
						case 'login-bad':
							echo "Incorrect login credentials. Please try again.";
							break;
						case 'register-exists':
							echo "User already exists.";
							break;
						case 'register-match':
							echo "Passwords do not match.";
							break;
						case 'register-invalid-name':
							echo "Username contained invalid characters.";
							break;
						case 'logout-failure':
							echo "Failed to log you out. Please try again or report the error to the admins.";
							break;
						default:
							echo "Encountered an unknown error.";
							break;
					}
					?>
				</p>
			</div>
		</div>
		
		<?php endif ?>
		
		<main id="content" class="wrapper">
			<?php 
			include(PATH . "views/$url.php");
			?>
		</main>
		
		<footer id="footer" class="wrapper">
			<div class="container">
				<span class="signoff">
					<?=$website?>
				</span>
				
				<span class="links">
					<a href="mailto:nozirored@gmail.com?subject=Contacting%20about%20Collections.com">Contact</a>
				</span>
				
				<span class="theme-selector list horizontal">
					<span class="item text"> Choose your theme:</span>
					<a id="theme-dark" class="item text" role="button">Dark</a>
					<a id="theme-light" class="item text" role="button">Light</a>
				</span>
				
				<!--<span class="copyright">
					&copy <?php echo date("Y"); ?> Noziro Red
				</span>-->
			</div>
		</footer>
		
		<div id="gdpr" class="wrapper <?php
			if(isset($_COOKIE["gdpr"])) {
				echo "hidden";
			}
		?>">
			<div class="container">
				<p>This website uses cookies to create a functioning user experience. By continuing to use this site, you agree to the use of these cookies.</p>
				
				<button id="gdpr-accept" class="button large">Dismiss</button>
			</div>
		</div>
	</body>
</html>

<?php

$db->close();

?>