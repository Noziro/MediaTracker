<div class="container">
	<?php if(isset($_COOKIE["session"]) && sqli_result("SELECT id FROM sessions WHERE id=?", "s", $_COOKIE["session"])->num_rows > 0) : ?>
	
	<p>Already logged in!</p>
	
	<?php elseif(isset($_GET["action"]) && $_GET["action"] == "register") : ?>
	
	<div class="login-form">
		<h5>Register</h5>
		
		<form action="/session" method="POST">
			<input type="hidden" name="action" value="register">
			
			<label for="login-username">Username</label>
			<input
				id="login-username"
				class=""
				type="text"
				name="username"
				maxlength="50"
				spellcheck="false"
				autocomplete="username"
				autofocus>
			<div class="reqs">
				<h6>Your username must...</h6>
				<ul class="checkmark-list">
					<li><span class="checkmark"></span> Only contain alphanumeric characters (a-z, 0-9), dashes (-), and underscores (_)</li>
					<li><span class="checkmark"></span> Not be more than 50 characters</li>
				</ul>
				<small>Username is case-insensitive. You can change your display name later.</small>
			</div>
			<br />
			
			<label for="login-email">Email (optional)</label>
			<input
				id="login-email"
				class=""
				type="email"
				name="email"
				maxlength="50"
				spellcheck="false"
				autocomplete="email">
			<br />
			
			<label for="login-password">Password</label>
			<input
				id="login-password"
				class=""
				type="password"
				name="password"
				maxlength="100"
				spellcheck="false"
				autocomplete="password">
			<div class="reqs">
				<h6>Your password must...</h6>
				<ul class="checkmark-list">
					<li><span class="checkmark"></span> Be three or more characters long</li>
					<li><span class="checkmark"></span> Not be more than 100 characters</li>
					<li><span class="checkmark"></span> Match</li>
				</ul>
				<small>Please do not use the same password as another service.</small>
			</div>
			<br />
			
			<input
				id="login-password-confirm"
				class=""
				type="password"
				name="password-confirm"
				maxlength="100"
				spellcheck="false"
				autocomplete="password">
			<br />
			
			<input type="submit" name="commit" value="Register" class="button large">
		</form>
	</div>
	
	<div class="login-prompt">
		<p class="center-text">Already registered? <a class="link" href="<?=FILEPATH?>login?action=login">Login here.</a></p>
	</div>
	
	<?php elseif(isset($_GET["action"]) && $_GET["action"] == "login") : ?>
	
	<div class="login-form">
		<h5>Login</h5>
		
		<form action="/session" method="POST">
			<input type="hidden" name="action" value="login">
			
			<label for="login-username">Username</label>
			<input
				id="login-username"
				class=""
				type="text"
				name="username"
				spellcheck="false"
				autocomplete="username"
				autofocus>
			<br />
			<small><a href="/forgot?q=username" class="forgot-login">Forgot username?</a></small>
			<br />
			
			<label for="login-password">Password</label>
			<input
				id="login-password"
				class=""
				type="password"
				name="password"
				spellcheck="false"
				autocomplete="password">
			<br />
			<small><a href="/forgot?q=password" class="forgot-login">Forgot password?</a></small>
			<br />
			
			<input type="submit" name="commit" value="Sign in" class="button large">
		</form>
	</div>
	
	<div class="login-prompt">
		<p class="center-text">Don't have an account? <a class="link" href="<?=FILEPATH?>login?action=register">Register here.</a></p>
	</div>
	
	<?php elseif(isset($_GET["action"]) && $_GET["action"] == "forgot") : ?>
	
	<p>not implemented yet lol</p>
	
	<?php else : ?>
	
	<div class="login-form">
		<a class="button large" href="?action=login">Login</a>
		<br />
		<a class="button large" href="?action=register">Signup</a>
	</div>
	
	<?php endif ?>
	
	</form>
</div>