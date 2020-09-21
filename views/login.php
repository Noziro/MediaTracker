<main id="content" class="wrapper wrapper--content">
	<div class="wrapper__inner login-flow">
		<?php if(isset($_COOKIE["session"]) && sql("SELECT id FROM sessions WHERE id=? LIMIT 1", "s", $_COOKIE["session"])['rows'] > 0) :
		
		header("Location: /");
		exit();
		
		elseif(isset($_GET["action"]) && $_GET["action"] == "register") : ?>
		
		<div class="login-flow__form">
			<h3 class="login-flow__title">Register</h5>
			
			<form action="/session" method="POST">
				<input type="hidden" name="action" value="register">
				
				<label class="login-flow__label" for="login-username">Username</label>
				<input
					id="login-username"
					class="login-flow__input"
					type="text"
					name="username"
					maxlength="50"
					spellcheck="false"
					autocomplete="username"
					required
					autofocus>
				<div class="reqs reqs--username">
					<!-- TODO - This requirements section does not follow any style guide and has a bunch of classes which have no styling as of yet. -->
					<h6>Your username must...</h6>
					<ul class="checkmark-list">
						<li><span class="checkmark"></span> Only contain alphanumeric characters (a-z, 0-9), dashes (-), and underscores (_)</li>
						<li><span class="checkmark"></span> Not be more than 50 characters</li>
					</ul>
					<small>Username is case-insensitive. You can change your display name later.</small>
				</div>
				
				<label class="login-flow__label" for="login-email">Email (optional)</label>
				<input
					id="login-email"
					class="login-flow__input"
					type="email"
					name="email"
					maxlength="254"
					spellcheck="false"
					autocomplete="email">
				
				<label class="login-flow__label" for="login-password">Password</label>
				<input
					id="login-password"
					class="login-flow__input"
					type="password"
					name="password"
					maxlength="72"
					spellcheck="false"
					autocomplete="password"
					required>
				
				<label class="login-flow__label" for="login-password-confirm">Confirm Password</label>
				<input
					id="login-password-confirm"
					class="login-flow__input"
					type="password"
					name="password-confirm"
					maxlength="72"
					spellcheck="false"
					autocomplete="password"
					required>
				<div class="reqs reqs--password">
					<h6>Your password must...</h6>
					<ul class="checkmark-list">
						<li><span class="checkmark"></span> Be 6 or more characters long</li>
						<li><span class="checkmark"></span> Not be more than 72 characters</li>
						<li><span class="checkmark"></span> Match</li>
					</ul>
					<small>Please do not use the same password as another service.</small>
				</div>
				
				<input type="submit" name="commit" value="Register" class="login-flow__button button button--medium">
			</form>
		</div>
		
		<div class="login-flow__prompt">
			Already registered? <a href="<?=FILEPATH?>login?action=login">Login here.</a>
		</div>
		
		<?php elseif(isset($_GET["action"]) && $_GET["action"] == "login") : ?>
		
		<div class="login-flow__form">
			<h5 class="login-flow__title">Login</h5>
			
			<form action="/session" method="POST">
				<input type="hidden" name="action" value="login">
				<input type="hidden" name="return_to" value="<?=$_GET['return_to']?>">
				
				<label class="login-flow__label" for="login-username">Username</label>
				<input
					id="login-username"
					class="login-flow__input"
					type="text"
					name="username"
					spellcheck="false"
					autocomplete="username"
					required
					autofocus>
					
				<a class="login-flow__subtext" href="/login?action=forgot&q=username">Forgot username?</a>
				
				<label class="login-flow__label" for="login-password">Password</label>
				<input
					id="login-password"
					class="login-flow__input"
					type="password"
					name="password"
					maxlength="72"
					spellcheck="false"
					autocomplete="password"
					required>
					
				<a class="login-flow__subtext" href="/login?action=forgot&q=password" class="forgot-login">Forgot password?</a>
				
				<input type="submit" name="commit" value="Sign In" class="login-flow__button button button--medium">
			</form>
		</div>
		
		<div class="login-flow__prompt">
			Don't have an account? <a href="<?=FILEPATH?>login?action=register">Register here.</a>
		</div>
		
		<?php elseif(isset($_GET["action"]) && $_GET["action"] == "forgot") : ?>
		
		<p>not implemented yet lol</p>
		
		<?php else : ?>
		
		<a class="login-flow__only-button button button--large" href="?action=login">Login</a>
		
		<a class="login-flow__only-button button button--large" href="?action=register">Register</a>
		
		<?php endif ?>
	</div>
</main>