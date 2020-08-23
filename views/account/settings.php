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
			<div class="divider"></div>
			<form action="/session" method="POST" class="item text logout" >
				<input type="hidden" name="action" value="logout">
				<input type="submit" name="commit" value="Logout" class="link">
			</form>
		</div>
	</div>
	
	<div class="split mainbar settings">
		<?php
		
		if(!isset($_GET['section']) || $_GET['section'] === 'profile') : ?>
		
		
		
		Change nickname<br />
		<br />
		Change about<br />
		<br />
		Change avatar<br />
		<br />
		Change banner
		
		
		
		<?php elseif($_GET['section'] === 'security') : ?>
		
		<h3 class="settings__header">Modify Account</h3>
		
		Change email<br />
		<br />
		Change password<br />
		<br />
		
		<h3 class="settings__header">Active Sessions</h3>
		
		<table class="active-sesions">
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
		
		<?php
		else :
			header("Location: /404");
			exit();
		
		endif;
		?>
	</div>
</div>