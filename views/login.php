<main id="content" class="wrapper wrapper--content">
	<div class="wrapper__inner login-flow">
		<div class="login-flow__form">
			<h5 class="login-flow__title">Login</h5>
			
			<form action="/interface/session" method="POST">
				<input type="hidden" name="action" value="login">
				<?php if(isset($_GET['return_to'])) : ?>
				<input type="hidden" name="return_to" value="<?=$_GET['return_to']?>">
				<?php endif; ?>
				
				<label class="login-flow__label label" for="login-username">Username</label>
				<input
					id="login-username"
					class="login-flow__input input"
					type="text"
					name="username"
					spellcheck="false"
					autocomplete="username"
					required
					autofocus>
					
				<a class="login-flow__subtext" href="/login?action=forgot&q=username">Forgot username?</a>
				
				<label class="login-flow__label label" for="login-password">Password</label>
				<input
					id="login-password"
					class="login-flow__input input"
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
			Don't have an account? <a href="/register">Register here.</a>
		</div>
	</div>
</main>