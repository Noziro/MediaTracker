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
	
	<div class="split mainbar">
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
		
		Change email<br />
		<br />
		Change password<br />
		<br />
		Active Sessions:
		
		<?php else :
		header("Location: /404");
		exit();
		endif ?>
	</div>
</div>