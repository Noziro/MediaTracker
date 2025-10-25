<?php

// SETUP

# Defines basic PATH for easy use across both client & server
define("PATH", $_SERVER["DOCUMENT_ROOT"] . "/");

# Includes various important variables and functions
include PATH."server/server.php";

# the URL as displayed in the client browse
DEFINE("URL", [
	'PATH_STRING' => strtok($_SERVER["REQUEST_URI"], '?'),
	'PATH_ARRAY' => remove_empties(explode('/', strtok($_SERVER["REQUEST_URI"], '?')))
]);

# Include HttpResponse class for error redirection
include PATH."server/http_response.php";



// INDEX-SPECIFIC SETUP

// Determine which file to load as the main page content

if( empty(URL['PATH_ARRAY']) ){
	$file = $has_session ? 'dashboard' : 'about';
	$page_title = $has_session ? 'Dashboard' : 'Track Your Collections!';
}
# match collection in the format of:
# - /my/collection (auto-detect your own collection)
# - /user/{ID}/collection (any user's collection)
# - /collection/{ID} (a specific collection)
elseif( count(URL['PATH_ARRAY']) <= 2 && URL['PATH_ARRAY'][0] === 'collection' ){
	$file = 'collection';
	$page_title = 'Collection';
}
elseif( URL['PATH_STRING'] === '/my/collection' ||
	URL['PATH_ARRAY'][0] === 'user' && count(URL['PATH_ARRAY']) === 3 && URL['PATH_ARRAY'][2] === 'collection' ){
	$file = 'collection';
	$page_title = 'Collection';
}
elseif( count(URL['PATH_ARRAY']) === 2 && URL['PATH_ARRAY'][0] === 'item' ){
	$file = 'item';
	$page_title = 'Item';
}
elseif( count(URL['PATH_ARRAY']) === 2 && URL['PATH_ARRAY'][0] === 'user' ){
	$file = 'user';
	$page_title = 'User';
}
elseif( count(URL['PATH_ARRAY']) === 3 && URL['PATH_ARRAY'][0] === 'item' && URL['PATH_ARRAY'][2] === 'edit' ){
	$file = 'item^edit';
	$page_title = 'Edit Item';
}
elseif( count(URL['PATH_ARRAY']) === 3 && URL['PATH_ARRAY'][0] === 'account' && URL['PATH_ARRAY'][1] === 'settings' ){
	$file = 'account^settings';
	$page_title = 'Settings / '.ucfirst(URL['PATH_ARRAY'][2]);
}
// TODO: this is rather garbage. Better to redirect all number error codes in .htaccess to error.php or something
elseif( count(URL['PATH_ARRAY']) === 1 && in_array(intval(URL['PATH_ARRAY'][0]), array_keys(HttpResponse::$error_codes)) && intval(URL['PATH_ARRAY'][0]) >= 400 ){
	$file = 'error';
}
else {
	# replaces all slashes with ^. This matches the file names in "views" folder
	$file = str_replace('/', '^', substr(URL['PATH_STRING'], 1));
}

// Set CSS and JS files to load based on $file

$static_files_to_load = [
	'css' => [
		'style',
		$file
	],
	'js' => [
		'scripts',
		$file
	]
];
if( count(URL['PATH_ARRAY']) > 0 && URL['PATH_ARRAY'][0] === 'register' ){
	$static_files_to_load['css'][] = 'login';
}
if( count(URL['PATH_ARRAY']) > 1 && URL['PATH_ARRAY'][1] === 'search' ){
	$static_files_to_load['css'][] = 'browse';
}

// Set page title.

# Defaults to page name unless set earlier or on index
$page_title = isset($page_title) ? $page_title : ucfirst(nth_last(URL['PATH_ARRAY']));

// Redirect or fail page depending on certain factors

# SQL connection must be OK
if( mysqli_connect_errno() ){
	finalize('/500');
}
# file must exist
if( file_exists("views/$file.php") === false ){
	finalize('/404');
}
# user cannot re-login
if( in_array($file, ['login', 'register']) && $has_session ){
	finalize('/');
}
?>

<!DOCTYPE HTML>
<html lang="en" class="t-light">
	<head>
		<title><?=SITE_NAME." - ".$page_title?></title>
		<meta charset="utf-8">
		<meta name="description" content="Placeholder">
		<meta name="keywords" content="Placeholder">
		<link rel="icon" type="image/png" href="/static/img/favicon.png">
		<link rel="icon" type="image/png" href="/static/img/favicon-32.png" sizes="32x32">
		<link rel="icon" type="image/png" href="/static/img/favicon-256.png" sizes="256x256">
		
		<?php
		foreach( $static_files_to_load['css'] as $css_file ){
			if( file_exists(PATH."static/css/".$css_file.".css") ){
				echo '<link rel="stylesheet" href="/static/css/'.$css_file.'.css">';
			}
		}
		foreach( $static_files_to_load['js'] as $js_file ){
			if( file_exists(PATH."static/js/".$js_file.".js") ){
				echo '<script type="text/javascript" src="/static/js/'.$js_file.'.js" defer></script>';
			}
		}
		?>
		
		<link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Montserrat:400,400i,600,600i|Roboto+Mono">
		
		<script type="text/javascript">
		<?php include PATH."static/js/include.js"; ?>
		</script>
	</head>
	<body class="page <?php
			if( isset($_GET['frame']) ){
				echo "page--frame ";
			}

			echo 't-'.implode(" t-", URL['PATH_ARRAY']);
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
					
					<a class="site-nav__item" href="/my/collection">Collection</a>
					
					<div class="dropdown notifications" style="display: none;">
						<a class="site-nav__item" href="/my/collection">!</a>
						
						<div class="dropdown-menu list vertical">
							Notifications -TODO-
						</div>
					</div>
					
					<div class="dropdown profile">
						<a class="site-nav__item" href="<?="/user/".$user["id"]?>"><?=$user["nickname"]?></a>
						
						<div class="dropdown-menu list vertical">
							<a class="site-nav__item" href="<?="/user/".$user["id"]?>">Profile</a>
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
		include PATH."views/$file.php";
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