<?php declare(strict_types=1); ?>

<main id="content" class="wrapper wrapper--content">
	<div class="wrapper__inner login-flow">
		
		<div class="login-flow__form">
			<h3 class="login-flow__title">Register</h5>
			
			<form action="/interface/session/register" method="POST">
				<label class="login-flow__label label" for="login-username">Username</label>
				<input
					id="login-username"
					class="login-flow__input input"
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
				
				<label class="login-flow__label label" for="login-email">Email (optional)</label>
				<input
					id="login-email"
					class="login-flow__input input"
					type="email"
					name="email"
					maxlength="254"
					spellcheck="false"
					autocomplete="email">
				
				<label class="login-flow__label label" for="login-password">Password</label>
				<input
					id="login-password"
					class="login-flow__input input"
					type="password"
					name="password"
					maxlength="72"
					spellcheck="false"
					autocomplete="new-password"
					required>
				
				<label class="login-flow__label label" for="login-password-confirm">Confirm Password</label>
				<input
					id="login-password-confirm"
					class="login-flow__input input"
					type="password"
					name="password-confirm"
					maxlength="72"
					spellcheck="false"
					autocomplete="new-password"
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
			Already registered? <a href="/login">Login here.</a>
		</div>
	</div>
</main>