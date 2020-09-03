<?php
if(!$has_session) {
	header('Location: /?error=require-sign-in');
	exit();
}
?>

<div class="wrapper__inner page-split">
	<div class="split sidebar">
		<div class="">
			<a class="" href="?section=profile">Profile</a>
			<a class="" href="?section=security">Security</a>
			<a class="" href="?section=preferences">Preferences</a>
			<div class="divider"></div>
			
			<button form="form-logout" class="item text logout" type="submit">Logout</button>
			
		</div>
	</div>
	
	<div class="split mainbar settings">
		<form id="form-settings" style="display:none" action="/interface" method="POST">
			<input type="hidden" name="action" value="change-settings">
		</form>
		
		<?php if(!isset($_GET['section']) || $_GET['section'] === 'profile') : ?>
		
		<label for="change-nickname" class="settings__label">Change nickname</label>
		<input form="form-settings" id="change-nickname" class="settings__input input input--disabled" type="text" placeholder="<?=$user['nickname']?>" disabled>

		<span class="settings__subtext">Your nickname is <b>not</b> your username! You will still sign in with your original username, but publicly you will your new nickname will display.</span>

		<label for="change-about" class="settings__label">Change about</label>
		<input form="form-settings" id="change-about" class="settings__input input input--disabled" type="text" value="" disabled>

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
			$profile__active_sessions = sqli_result_bindvar('SELECT id, started, expiry, user_ip FROM sessions WHERE user_id=?', 's', $user['id']);
			$profile__active_sessions = $profile__active_sessions->fetch_all(MYSQLI_ASSOC);
			
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
		
		
		
		<?php elseif($_GET['section'] === 'preferences') : ?>
		
		<h3 class="settings__header">User Experience</h3>
		
		<label class="label">Change timezone</label>
		<select class="select" form="form-settings" name="change-timezone">
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
		
		
		
		
		
		<?php
		else :
			header("Location: /404");
			exit();
		
		endif;
		?>
	</div>
</div>