<?php
if(!$has_session) {
	header('Location: /?error=require-sign-in');
	exit();
}
?>

<main id="content" class="wrapper wrapper--content">
	<div class="wrapper__inner split">
		<div class="split__section split__section--sidebar">
			<span class="split__sidebar-header">Settings</span>

			<a class="split__sidebar-item" href="?section=profile">Profile</a>
			<a class="split__sidebar-item" href="?section=security">Security</a>
			<a class="split__sidebar-item" href="?section=privacy">Privacy</a>
			<a class="split__sidebar-item" href="?section=preferences">Preferences</a>
			<a class="split__sidebar-item" href="?section=data">Data</a>
		</div>
		


		<div class="split__section split__section--primary">
			<form id="form-settings" style="display:none" action="/interface/generic" method="POST">
				<input type="hidden" name="action" value="change_settings">
				<input type="hidden" name="return_to" value="<?=$_SERVER['REQUEST_URI']?>">
			</form>
			
			<?php if(!isset($_GET['section']) || $_GET['section'] === 'profile') : ?>

			<h3 class="settings__header">User Profile</h3>
			
			<label for="change-nickname" class="settings__label">Change nickname</label>
			<input form="form-settings" id="change-nickname" class="input input--disabled" type="text" max="50" placeholder="<?=$user['nickname']?>" disabled>

			<span class="settings__subtext">Your nickname is <b class="u-bold">not</b> your username! You will still sign in with your original username, but publicly your new nickname will display.</span>

			<label for="change-about" class="settings__label">Change about</label>
			<input form="form-settings" id="change-about" class="input input--disabled" type="text" value="" disabled>

			<span class="settings__notice">Avatar & banner functionality will come in the future.</span>

			<div class="settings__button button-list">
				<button form="form-settings" class="settings__button button" type="submit">
					Apply
				</button>
				
				<!--<button class="settings__button button" type="button" onclick="location.reload()">
					Discard
				</button>-->
			</div>
			
			


			
			<?php elseif($_GET['section'] === 'security') : ?>
			
			<h3 class="settings__header">Modify Account</h3>
			
			<label for="change-email" class="settings__label">Change email</label>
			
			
			<label for="change-password" class="settings__label">Change password</label>
			

			<div class="settings__button button-list">
				<button form="form-settings" class="settings__button button" type="submit">
					Apply
				</button>
				
				<!--<button class="settings__button button" type="button" onclick="location.reload()">
					Discard
				</button>-->
			</div>
			
			<h3 class="settings__header">Active Sessions</h3>
			
			<table class="active-sessions">
				<tr class="active-sesions__row">
					<th class="active-sessions__cell active-sessions__cell--header">Session ID</th>
					<th class="active-sessions__cell active-sessions__cell--header">Date Started</th>
					<th class="active-sessions__cell active-sessions__cell--header">Expires</th>
					<th class="active-sessions__cell active-sessions__cell--header">User IP</th>
				</tr>
				
				<?php
				$profile__active_sessions = sql('SELECT id, started, expiry, user_ip FROM sessions WHERE user_id=? ORDER BY started DESC', ['i', $user['id']])['result'];
				
				foreach($profile__active_sessions as $session) : ?>
				
				<tr class="active-sesions__row">
					<td class="active-sessions__cell active-sessions__cell--content">
						<?=$session['id']?>
					</td>
					<td class="active-sessions__cell active-sessions__cell--content">
						<?=utc_date_to_user($session['started'])?>
					</td>
					<td class="active-sessions__cell active-sessions__cell--content">
						<?=utc_date_to_user(gmdate("Y-m-d H:i:s", $session['expiry']))?>
					</td>
					<td class="active-sessions__cell active-sessions__cell--content">
						<?=$session['user_ip']?>
					</td>
				</tr>
				
				<?php endforeach; ?>
			</table>

			<form id="form-logout-all" style="display:none" action="/interface/session" method="POST">
				<input type="hidden" name="action" value="logout_all">
				<input type="hidden" name="return_to" value="<?=$_SERVER['REQUEST_URI']?>">
			</form>
			
			<button class="settings__button button button--spaced" onclick="modalConfirmation('Are you sure you wish to logout all sessions?', 'logout_all', '', '', '/interface/session')">
				Logout All Sessions
			</button>


			
			<?php elseif($_GET['section'] === 'preferences') : ?>
			
			<h3 class="settings__header">User Experience</h3>
			
			<label class="label">Change timezone</label>
			<select class="select" form="form-settings" name="change_timezone">
				<?php foreach($valid_timezones as $zone_group_label => $zone_group) : ?>
				<optgroup label="<?=$zone_group_label?>">
					<?php foreach($zone_group as $zone) : ?>
					<option <?php if($zone === $prefs['timezone']) { echo "selected"; } ?>>
						<?=$zone?>
					</option>
					<?php endforeach; ?>
				</optgroup>
				<?php endforeach; ?>
			</select>

			<div class="settings__button button-list">
				<button form="form-settings" class="settings__button button button--spaced" type="submit">
					Apply
				</button>
				
				<!--<button class="settings__button button" type="button" onclick="location.reload()">
					Discard
				</button>-->
			</div>





			<?php elseif($_GET['section'] === 'privacy') : ?>

			<h3 class="settings__header">Collection Options</h3>

			<label class="label">Who can view your lists</label>
			- Anyone - Just friends - Only me


			<label class="label">Hide adult entries</label>
			Yes/no






			<?php elseif($_GET['section'] === 'data') : ?>

			<h3 class="settings__header">Import/Export Lists</h3>

			<form action="/interface/generic" method="POST">
				<input type="hidden" name="action" value="import_list">
				<input type="hidden" name="return_to" value="<?=$_SERVER['REQUEST_URI']?>">

				<label class="settings__label">File to import</label>
				<input type="file" name="file">

				<label class="label">What data are you importing?</label>

				<label for="parser-in-house" class="checkbox">
					<input id="parser-in-house" type="radio" name="parser" value="in_house">
					<?=$website.$domain?>
				</label>

				<label for="parser-mal" class="checkbox">
					<input id="parser-mal" type="radio" name="parser" value="mal" checked>
					MyAnimeList
				</label>

				<label for="parser-imdb" class="checkbox">
					<input id="parser-imdb" type="radio" name="parser" value="imdb">
					IMDB
				</label>

				<label class="label">Which collection should it be imported to?</label>
					<?php
					$collections = sql('SELECT id, name, type FROM collections WHERE user_id=? AND deleted=0 ORDER BY name ASC', ['i', $user['id']])['result'];
					$i = 0;
					foreach($collections as $collection) :
					?>
					<label>
						<input type="radio" name="collection_id" value="<?=$collection['id']?>" <?php if($i === 0) { echo "checked"; $i = 1; } ?>>
						<?=$collection['name']?> (<?=$collection['type']?>)
					</label>
					<?php endforeach; ?>
				</label>

				<button form="form-settings" class="button button--spaced" type="submit">
					Import
				</button>
			</form>

			<span class="settings__notice">List exporting coming soon.</span>
			<!--

			<form action="/interface/generic" method="POST">
				<input type="hidden" name="action" value="export-list">
				<button class="settings__button button button--spaced" type="submit">
					Export
				</button>
			</form>

			-->
			
			
			
			
			<?php
			else :
				header("Location: /404");
				exit();
			
			endif;
			?>



			<div id="modal--confirmation" class="modal modal--hidden" role="dialog" aria-modal="true">
			<button class="modal__background" onclick="toggleModal('modal--confirmation', false)"></button>
			<div class="modal__inner">
				<h3 id="js-confirmation-msg" class="modal__header"></h3>
				<div class="js-confirmation-preview"><!-- TODO - unused atm - plan to put post content here to display what user is deleting --></div>
				<form id="form-confirmation" action="/interface/generic" method="POST" style="display:none">
					<input id="js-confirmation-action" type="hidden" name="action">
					<input type="hidden" name="return_to" value="<?=$_SERVER['REQUEST_URI']?>">
					<input id="js-confirmation-data" type="hidden">
				</form>
				<div class="button-list">
					<button form="form-confirmation" class="button-list__button button button--medium button--negative" type="submit">Confirm</a>
					<button class="button-list__button button button--medium" onclick="toggleModal('modal--confirmation', false)">Cancel</a>
				</div>
			</div>
		</div>
		</div>
	</div>
</main>