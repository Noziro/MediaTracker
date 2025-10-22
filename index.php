<?php

# REQUIRED SETUP

# Defines basic PATH for easy use across both client & server
define("PATH", $_SERVER["DOCUMENT_ROOT"] . "/");

# Includes various important variables and functions
include PATH."server/server.php";



# INDEX-SPECIFIC SETUP

# the URL as displayed in the client browser
$external_url = [
	'path_string' => strtok($_SERVER["REQUEST_URI"], '?'),
	'path_array' => array_filter(explode('/', strtok($_SERVER["REQUEST_URI"], '?')))
];

# Strips GET variables off the end and returns only the base URL
$url = $external_url['path_string'];

# Check for various conditions related to the URL and make usable later in code

if( $url == '/' && !$has_session ){
	$url = 'about';
}
elseif( $url == '/' && $has_session ){
	$url = 'index';
}
else {
	// strips the / off the beginning
	$url = substr($url, 1);
}

// replaces all slashes with ^. This matches the file names in "views" folder
$url = str_replace('/', '^', $url);

if( $url === 'collection^orphans' ){
	$url = 'collection';
}
if( file_exists("views/$url.php") != 1 ){
	finalize('/404');
}

# Check for SQL connection
if( mysqli_connect_errno() ){
	#500
	http_response_code(500);
	$url = '500';
}

$url_split = explode('^', $url);
$url_readable = end($url_split);

if( $has_session && $url_readable === 'login'
	|| $has_session && $url_readable === 'register'
	) {
	finalize('/');
}

// TODO - this is rather garbage. Better to redirect all number error codes in .htaccess to error.php or something
if( $url_readable === '403' || $url_readable === '404' || $url_readable === '500' ){
	$file = 'error';
} elseif( $url_readable === 'register' ){
	$file = 'login';
} else {
	$file = $url;
}

?>

<!DOCTYPE HTML>
<html lang="en" class="t-light">
	<head>
		<title><?php
			echo SITE_NAME . " - ";
			if( $url == "index" ){
				echo "Track Your Collections!";
			} else {
				echo ucfirst($url_readable);
			}
		?></title>
		<meta charset="utf-8">
		<meta name="description" content="Placeholder">
		<meta name="keywords" content="Placeholder">
		<link rel="icon" type="image/png" href="/static/img/favicon.png">
		<link rel="icon" type="image/png" href="/static/img/favicon-32.png" sizes="32x32">
		<link rel="icon" type="image/png" href="/static/img/favicon-256.png" sizes="256x256">
		
		<link rel="stylesheet" href="/static/css/style.css">
		
		<?php if (file_exists(PATH."static/css/".$file.".css")) : ?>
		<link rel="stylesheet" href="<?="/static/css/".$file?>.css">
		<?php endif ?>
		
		<?php if (file_exists(PATH."static/js/".$file.".js")) : ?>
		<script type="text/javascript" src="<?="/static/js/".$file.".js"?>" defer></script>
		<?php endif ?>
		
		<!--<script type="text/javascript" src="/static/js/jquery-3.3.1.min.js" async></script>-->
		<script type="text/javascript" src="/static/js/scripts.js" defer></script>
		
		<link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Montserrat:400,400i,600,600i|Roboto+Mono">
		
		<script type="text/javascript">
		<?php include PATH."static/js/include.js"; ?>
		</script>
	</head>
	<body class="page <?php
			if( isset($_GET['frame']) ){
				echo "page--frame ";
			}

			echo 't-'.implode(" t-", $url_split);
		?>">
		<?php if(!isset($_GET['frame'])) : ?> 
		<nav id="nav" class="wrapper wrapper--site-nav">
			<div class="wrapper__inner site-nav">
				<div class="site-nav__section">
					<a class="site-nav__identity" href="/">
						<?=SITE_NAME?>
					</a>
				</div>
				
				<div class="site-nav__section">
					<a class="site-nav__item" href="/browse">Browse</a>
				</div>
				
				<div class="site-nav__section site-nav--search">
					<input id="search" type="search" autocomplete="off" class="search-bar" placeholder="Search for Movies, Games, TV, Books, Anime, and more...">
					<button id="search-btn" type="button" class="button">Search</button>
				</div>
				
				<div class="site-nav__section">
					<?php if($has_session) : ?>
					
					<a class="site-nav__item" href="/collection">Collection</a>
					
					<div class="dropdown notifications" style="display: none;">
						<a class="site-nav__item" href="/collection">!</a>
						
						<div class="dropdown-menu list vertical">
							Notifications -TODO-
						</div>
					</div>
					
					<div class="dropdown profile">
						<a class="site-nav__item" href="<?="/user?u=".$user["id"]?>"><?=$user["nickname"]?></a>
						
						<div class="dropdown-menu list vertical">
							<a class="site-nav__item" href="<?="/user?u=".$user["id"]?>">Profile</a>
							<a class="site-nav__item" href="/account/settings">Settings</a>
							
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
					
					<a class="site-nav__item" href="/login?return_to=<?=urlencode($_SERVER["REQUEST_URI"])?>">Login</a>
					<a class="site-nav__item" href="/register">Register</a>
					
					<?php endif ?>
				</div>
			</div>
		</nav>
		<?php endif; ?>
		
		<?php
		if(isset($_SESSION['notice'])) :
		foreach($_SESSION['notice'] as $notice) :
		?>
		
		<div class="wrapper <?php
			if( $notice->type === 'error' ){
				echo "wrapper--notice-error";
			} else {
				echo "wrapper--notice";
			}
		?>">
			<div class="wrapper__inner notice">
				<?php
				echo $notice->message;
				echo '<div>'.$notice->details.'</div>';
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
					<span class="footer__section-head"><?=SITE_NAME?></span>
					<!-- <span class="footer__item">A project by Noziro Red</span> -->
					<a class="footer__item" href="/about">About</a>
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