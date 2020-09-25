<?php
# Temp until site name is decided
$website = "Collections";
$domain = ".com";



# REQUIRED SETUP

# Defines basic PATH for easy use across both client & server
define("PATH", $_SERVER["DOCUMENT_ROOT"] . "/");

# Includes various important variables and functions
include PATH."server/server.php";



# INDEX-SPECIFIC SETUP

# Strips GET variables off the end and returns only the base URL
$url = strtok($_SERVER["REQUEST_URI"], '?');

# Check for various conditions related to the URL and make usable later in code

if($url == '/' && !$has_session) {
	$url = 'about';
}
elseif($url == '/' && $has_session) {
	$url = 'index';
}
elseif(file_exists("views/$url.php") != 1) {
	header('Location: /404');
}
else {
	#generic pages - strips the / off the beginning
	$url = substr($url, 1);
}

# Check for SQL connection
if(mysqli_connect_errno()) {
	#500
	http_response_code(500);
	$url = '500';
}

$url_split = explode('/', $url);
$url_readable = end($url_split);

?>

<!DOCTYPE HTML>
<html lang="en" class="t-light">
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
		
		<?php
		// TODO - this is rather garbage. Better to redirect all number error codes in .htaccess to error.php or something
		if($url_readable === '403' || $url_readable === '404' || $url_readable === '500') {
			$file = 'error';
		} else {
			$file = $url_readable;
		}
		if (file_exists(PATH."static/css/".$file.".css")) :
		?>
		<link rel="stylesheet" href="<?=FILEPATH."static/css/".$file?>.css">
		<?php endif ?>
		
		<?php if (file_exists(PATH."static/js/".$url_readable.".js")) : ?>
		<script type="text/javascript" src="<?=FILEPATH."static/js/".$url_readable.".js"?>" defer></script>
		<?php endif ?>
		
		<!--<script type="text/javascript" src="<?=FILEPATH?>static/js/jquery-3.3.1.min.js" async></script>-->
		<script type="text/javascript" src="<?=FILEPATH?>static/js/scripts.js" defer></script>
		
		<link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Montserrat:400,400i,600,600i|Roboto+Mono">
		
		<script type="text/javascript">
		<?php include PATH."static/js/include.js"; ?>
		</script>
	</head>
	<body class="page <?php
			if(isset($_GET['frame'])) {
				echo "page--frame ";
			}

			echo 't-'.implode(" t-", $url_split);
		?>">
		<?php if(!isset($_GET['frame'])) : ?> 
		<nav id="nav" class="wrapper wrapper--site-nav">
			<div class="wrapper__inner site-nav">
				<div class="site-nav__section">
					<a class="site-nav__identity" href="<?=FILEPATH?>">
						<?=$website?>
					</a>
				</div>
				
				<div class="site-nav__section">
					<a class="site-nav__item" href="<?=FILEPATH?>database">Database</a>
					<a class="site-nav__item" href="<?=FILEPATH?>forum">Forum</a>
					<a class="site-nav__item" href="<?=FILEPATH?>groups">Groups</a>
				</div>
				
				<div class="site-nav__section site-nav--search">
					<input type="search" autocomplete="off" class="search-bar" placeholder="Search for Movies, Games, TV, Books, Anime, and more...">
				</div>
				
				<div class="site-nav__section">
					<?php if($has_session) : ?>
					
					<a class="site-nav__item" href="<?=FILEPATH?>collection">Collection</a>
					
					<div class="dropdown notifications" style="display: none;">
						<a class="site-nav__item" href="<?=FILEPATH?>collection">!</a>
						
						<div class="dropdown-menu list vertical">
							Notifications -TODO-
						</div>
					</div>
					
					<div class="dropdown profile">
						<a class="site-nav__item" href="<?=FILEPATH."user?id=".$user["id"]?>"><?=$user["nickname"]?></a>
						
						<div class="dropdown-menu list vertical">
							<a class="site-nav__item" href="<?=FILEPATH."user?id=".$user["id"]?>">Profile</a>
							<a class="site-nav__item" href="<?=FILEPATH?>account/settings">Settings</a>
							
							<form id="form-logout"style="display:none" action="/interface/session" method="POST">
								<input type="hidden" name="action" value="logout">
								<input type="hidden" name="return_to" value="<?=$_SERVER["REQUEST_URI"]?>">
							</form>
							
							<button form="form-logout" class="c-text-button" type="submit">
								<span class="site-nav__item">
									Logout
								</span>
							</button>
						</div>
					</div>
						
					<?php else : ?>
					
					<a class="site-nav__item" href="<?=FILEPATH?>login?action=login&return_to=<?=urlencode($_SERVER["REQUEST_URI"])?>">Login</a>
					<a class="site-nav__item" href="<?=FILEPATH?>login?action=register">Register</a>
					
					<?php endif ?>
				</div>
			</div>
		</nav>
		<?php endif; ?>
		
		<?php
		if(isset($_SESSION['notice'])) :
		$msg = $_SESSION['notice'];
		foreach($_SESSION['notice'] as $msg) :
		?>
		
		<div class="wrapper <?php
			if($msg['type'] === 'error') {
				echo "wrapper--notice-error";
			} else {
				echo "wrapper--notice";
			}
		?>">
			<div class="wrapper__inner notice">
				<?php
				switch($msg['case']) {
					// Notices
					case 'login_success':
						echo "Successfully logged into your account. Welcome back!";
						break;
					case 'register_success':
						echo "Successfully created your account. Have fun!";
						break;
					case 'logout_success':
						echo "Successfully logged out of your account. Thanks for visiting.";
						break;
					case 'success':
						echo "Action performed successfully.";
						break;
					case 'partial_success':
						echo "Action partially succeeded - some updates failed. See below for details.";
						break;
					case 'no_change_detected':
						echo "No changes were applied, as none were detected.";
						break;
					
					// Errors
					case 'required_field':
						echo "Please fill out the required fields.";
						break;
					case 'login_bad':
						echo "Incorrect login credentials. Please try again.";
						break;
					case 'register_exists':
						echo "User already exists.";
						break;
					case 'register_match':
						echo "Passwords do not match.";
						break;
					case 'invalid_name':
						echo "Name contains invalid characters.";
						break;
					case 'invalid_pass':
						echo "Password does not meet requirements.";
						break;
					case 'logout_failure':
						echo "Failed to log you out. Please try again or report the error to the admins.";
						break;
					case 'require_sign_in':
						echo "Please sign in before attempting this action.";
						break;
					case 'database_failure':
						echo "An error occured in the server database while performing your request.";
						break;
					case 'database_null_commit':
						echo "An attempted database commit did not result in an action.";
						break;
					case 'disallowed_action':
						echo "Attempted to perform an invalid or unrecognized action.";
						break;
					case 'unauthorized':
						echo "Attempted operation outside of user authority.";
						break;
					case 'invalid_value':
						echo "A value you entered was invalid or out of expected bounds. Please try again.";
						break;

					case 'blank':
						break;

					// Default
					default:
						echo "This was meant to say something, but it doesn't!";
						break;
				}
				echo '<div>'.$msg['details'].'</div>';
				?>
			</div>
		</div>
		
		<?php
		endforeach;
		endif;
		?>
		
		<?php 
		include PATH."views/$url.php";
		?>
		
		<?php if(!isset($_GET['frame'])) : ?> 
		<footer id="footer" class="wrapper wrapper--footer">
			<div class="wrapper__inner footer">
				<div class="footer__section links">
					<span class="footer__section-head"><?=$website.$domain?></span>
					<!-- <span class="footer__item">A project by Noziro Red</span> -->
					<a class="footer__item" href="<?=FILEPATH?>about">About</a>
					<a class="footer__item" href="mailto:nozirored@gmail.com?subject=<?=urlencode('Contacting about '.$website.$domain)?>">Contact</a>
					<a class="footer__item" href="https://github.com/Noziro/MediaTracker">Source</a>
				</div>
				
				<div class="footer__section">
					<span class="footer__section-head">Themes</span>
					
					<div class="footer__themes">
						<?php $themes = [
							'light',
							'dark',
							'blackout',
							'contrast'
						];
						
						foreach($themes as $theme) : ?>
						
						<a id="theme-<?=$theme?>" class="footer__theme-option theme-preview" role="button" onclick="selectTheme(this.getAttribute('data-value'))" data-value="<?=$theme?>">
							<div class="theme-preview__backing t-<?=$theme?>" aria-hidden="true">
								<span class="theme-preview__text">Aa</span>
							</div>
							
							<div class="global__accessibility-text">Select <?=$theme?> theme.</div>
						</a>
						
						<?php endforeach; ?>
					</div>
				</div>

				<div class="footer__section">
					<span class="footer__subtext">
						Page generated in 
						<?php
						$pl_timer_end = hrtime(True);
						$pl_timer = $pl_timer_end - $pl_timer_start;
						echo $pl_timer/1e+6." milliseconds.";
						?>
					</span>
				</div>
			</div>
		</footer>
		<?php endif; ?>
		
		<?php if(!isset($_COOKIE["gdpr"])) : ?>
		
		<div id="gdpr" class="wrapper wrapper--gdpr">
			<div class="wrapper__inner gdpr">
				<p class="gdpr__dialogue">This website uses cookies to create a functioning user experience. By continuing to use this site, you agree to the use of these cookies.</p>
				
				<button id="gdpr-accept" class="button button--medium button--call-to-action">Dismiss</button>
			</div>
		</div>
		
		<?php endif ?>
	</body>
</html>

<?php

// Clear any temporary messages
$_SESSION['notice'] = null;

// Close DB
$db->close();

?>