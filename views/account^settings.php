<?php
if( !$has_session ){
	bailout('/', 'require_sign_in');
}

$section = count(URL['PATH_ARRAY']) === 3 ? URL['PATH_ARRAY'][2] : '';

$user_extra = sql('SELECT about FROM users WHERE id=?', ['i', $user['id']]);
$user = array_merge($user, $user_extra->rows[0]);
?>

<main id="content" class="wrapper wrapper--content">
	<div class="wrapper__inner l-split">
		<div class="c-module c-module--spacious l-split__section c-module c-module--spacious l-split__section--sidebar">
			<span class="l-split__sidebar-header">Settings</span>

			<a class="l-split__sidebar-item" href="/account/settings/profile">Profile</a>
			<a class="l-split__sidebar-item" href="/account/settings/security">Security</a>
			<a class="l-split__sidebar-item" href="/account/settings/privacy">Privacy</a>
			<a class="l-split__sidebar-item" href="/account/settings/preferences">Preferences</a>
			<a class="l-split__sidebar-item" href="/account/settings/data">Data</a>
		</div>
		


		<div class="c-module c-module--spacious l-split__section c-module c-module--spacious l-split__section--primary">
			<form id="form-settings" style="display:none" action="/interface/generic" method="POST" enctype="multipart/form-data">
				<input type="hidden" name="action" value="change_settings">
				<input type="hidden" name="return_to" value="<?=$_SERVER['REQUEST_URI']?>">
			</form>
			
			<?php if( $section === '' || $section === 'profile' ) : ?>

			<div class="l-leave-a-gap">
				<h3 class="c-heading-minor">User Profile</h3>

				<label for="change-nickname" class="settings__label label">Change nickname</label>
				<input form="form-settings" id="change-nickname" class="input js-autofill" name="nickname" type="text" max="50" autocomplete="username" data-autofill="<?=$user['nickname']?>">

				<span class="settings__subtext">Your nickname is <b class="u-bold">not</b> your username! You will still sign in with your original username, however your new nickname will be displayed to others.</span>

				<label class="settings__label label label--disabled">Username <span class="label__desc">(not changeable)</span></label>
				<input class="input input--disabled js-autofill" type="text" data-autofill="<?=$user['username']?>" disabled>
				
				<label for="change-about" class="settings__label label">Change about</label>
				<textarea
					form="form-settings"
					id="change-about"
					class="text-input text-input--resizable-v js-autofill"
					name="about"
					type="text"
					spellcheck="true"
					data-autofill="<?=$user['about']?>"></textarea>

				<label class="settings__label label">Change profile image</label>
				
				<?php if(!empty($user['profile_image'])) : ?>
				<img src="<?=$user['profile_image']?>" style="width: 30px; height: 30px; object-fit: cover;" />
				<?php endif; ?>
				<input class="file-upload" type="file" name="profile_image" accept=".jpg,.png" form="form-settings">

				<label class="settings__label label">Change banner image</label>
				
				<?php if(!empty($user['banner_image'])) : ?>
				<img src="<?=$user['banner_image']?>" style="width: 30px; height: 30px; object-fit: cover;" />
				<?php endif; ?>
				<input class="file-upload" type="file" name="banner_image" accept=".jpg,.png" form="form-settings">

				<br /><br />

				<div class="settings__button l-button-list">
					<button form="form-settings" class="settings__button button" type="submit">
						Apply
					</button>
					
					<!--<button class="settings__button button" type="button" onclick="location.reload()">
						Discard
					</button>-->
				</div>
			</div>
			
			


			
			<?php elseif( $section === 'security' ) : ?>
			
			<div class="l-leave-a-gap">
				<h3 class="c-heading-minor">Modify Account</h3>
				
				<label for="change-email" class="settings__label label">Change email</label>
				<input
					form="form-settings"
					id="change-email"
					class="settings__input input input--disabled"
					type="email"
					name="email"
					maxlength="254"
					spellcheck="false"
					autocomplete="email"
					disabled>
				<span class="subtext">You will be sent an email to confirm this change.</span>
			</div>

			<div class="l-leave-a-gap">
				<h3 class="c-heading-minor">Change Password</h3>

				<div class="settings__aside">
					<h6>Your password must...</h6>
					<ul class="checkmark-list">
						<li><span class="checkmark"></span> Be 6 or more characters long</li>
						<li><span class="checkmark"></span> Not be more than 72 characters</li>
						<li><span class="checkmark"></span> Match</li>
					</ul>
					<small>Please do not use the same password as another service.</small>
				</div>
				
				<label for="previous-password" class="settings__label label">Previous password</label>
				<input
					form="form-settings"
					id="previous-password"
					class="settings__input input"
					type="password"
					name="previous_password"
					maxlength="72"
					spellcheck="false"
					autocomplete="current-password">
				
				<label for="new-password" class="settings__label label">New password</label>
				<input
					form="form-settings"
					id="new-password"
					class="settings__input input"
					type="password"
					name="new_password"
					maxlength="72"
					spellcheck="false"
					autocomplete="new-password">
				
				<label for="new-password-confirm" class="settings__label label">Confirm new password</label>
				<input
					form="form-settings"
					id="new-password-confirm"
					class="settings__input input"
					type="password"
					name="new_password_confirm"
					maxlength="72"
					spellcheck="false"
					autocomplete="new-password">

				<div class="settings__button l-button-list">
					<button form="form-settings" class="settings__button button" type="submit">
						Apply
					</button>
					
					<!--<button class="settings__button button" type="button" onclick="location.reload()">
						Discard
					</button>-->
				</div>
			</div>
			
			<div class="l-leave-a-gap">
				<h3 class="c-heading-minor">Active Sessions</h3>
				
				<table class="active-sessions">
					<tr class="active-sesions__row">
						<th class="active-sessions__cell active-sessions__cell--header">Session ID</th>
						<th class="active-sessions__cell active-sessions__cell--header">Date Started</th>
						<th class="active-sessions__cell active-sessions__cell--header">Expires</th>
						<th class="active-sessions__cell active-sessions__cell--header">User IP</th>
					</tr>
					
					<?php
					$profile__active_sessions = sql('SELECT id, started, expiry, user_ip FROM sessions WHERE user_id=? ORDER BY started DESC', ['i', $user['id']])->rows;
					
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
			</div>


			
			<?php elseif( $section === 'preferences' ) : ?>
			
			<div class="l-leave-a-gap">
				<h3 class="c-heading-minor">User Experience</h3>
				
				<label class="label">Change timezone</label>
				<select class="select" form="form-settings" name="timezone">
					<?php foreach(VALID_TIMEZONES as $zone_group_label => $zone_group) : ?>
					<optgroup label="<?=$zone_group_label?>">
						<?php foreach($zone_group as $zone) : ?>
						<option <?php if( isset($user['timezone']) && $zone === $user['timezone'] || $zone === 'UTC' ){ echo "selected"; } ?>>
							<?=$zone?>
						</option>
						<?php endforeach; ?>
					</optgroup>
					<?php endforeach; ?>
				</select>

				<label for="change-colour" class="label">Change profile colour</label>
				<input form="form-settings" id="change-colour" type="color" name="profile_colour" value="<?php
						if( isset($user['profile_colour']) && $user['profile_colour'] !== null ){
							echo $user['profile_colour'];
						} else {
							echo '#ff3333';
						}
						?>">
				<label class="checkbox">
					<input form="form-settings" type="checkbox" name="reset_profile_colour" value="1">
					Reset colour
				</label>

				<div class="settings__button l-button-list">
					<button form="form-settings" class="settings__button button button--spaced" type="submit">
						Apply
					</button>
					
					<!--<button class="settings__button button" type="button" onclick="location.reload()">
						Discard
					</button>-->
				</div>
			</div>





			<?php elseif( $section === 'privacy' ) : ?>

			<div class="l-leave-a-gap">
				<h3 class="c-heading-minor">Collection Options</h3>

				<label class="label">Who can view your lists</label>
				- Anyone - Just friends - Only me
			</div>





			<?php elseif( $section === 'data' ) : ?>

			<div class="l-leave-a-gap">
				<h3 class="c-heading-minor">Import/Export Lists</h3>

				<form action="/interface/generic" method="POST">
					<input type="hidden" name="action" value="import_list">
					<input type="hidden" name="return_to" value="<?=$_SERVER['REQUEST_URI']?>">

					<label class="settings__label label">File to import</label>
					<input type="file" name="file">

					<label class="label">What data are you importing?</label>

					<label for="parser-in-house" class="checkbox">
						<input id="parser-in-house" type="radio" name="parser" value="in_house">
						<?=SITE_NAME?>
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
						$collections = sql('SELECT id, name, type FROM collections WHERE user_id=? AND deleted=0 ORDER BY name ASC', ['i', $user['id']])->rows;
						$i = 0;
						foreach($collections as $collection) :
						?>
						<label>
							<input type="radio" name="collection_id" value="<?=$collection['id']?>" <?php if( $i === 0 ){ echo "checked"; $i = 1; } ?>>
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
			</div>
			
			
			
			
			<?php
			else :
				// TODO - this fails
				#bailout('/404');
			
			endif;
			?>



			<?php include PATH.'server/includes/modal-confirmation.inc'; ?>
		</div>
		</div>
	</div>
</main>