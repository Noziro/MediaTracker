<!-- if USER SETUP == completed -->

<?php
if( !$has_session ){
	bailout('/');
}
?>

<main id="content" class="wrapper wrapper--content">
	<div class="wrapper__inner">
		<div class="c-welcome">
			<div id="js-advance-slider" class="c-welcome__slider">
				<div class="c-welcome__slide">
					<h2 class="c-welcome__title">Welcome, <?=$user['nickname']?>!</h2> 
					
					Your account has been been created, and is ready for you to start collecting!
					
					If you'd like to setup the basics now, please continue. These settings can all be modified later.

					<div class="l-button-list l-button-list--right">
						<button class="l-button-list__button button" onclick="incrementSlider()">Continue</button>
					</div>
				</div>

				
				<div class="c-welcome__slide">
					<h3 class="c-welcome__title">Collections</h3>
					<pre>
					Choose some defaults to get you started

						Choose your rating system:

					Import your media from:
						MyAnimeList
						More...
					</pre>

					<div class="l-button-list l-button-list--right">
						<button class="l-button-list__button button button--unimportant" onclick="decrementSlider()">Go Back</button>
						<button class="l-button-list__button button" onclick="incrementSlider()">Continue</button>
					</div>
				</div>


				<div class="c-welcome__slide">
					<h3 class="c-welcome__title">User Settings</h3>
					<pre>
					Set about:

					Choose a timezone
					</pre>

					<div class="l-button-list l-button-list--right">
						<button class="l-button-list__button button button--unimportant" onclick="decrementSlider()">Go Back</button>
						<a class="l-button-list__button button" href="/user">Finish</a>
					</div>
				</div>
			</div>
		</div>

		<br>
		<br>
		


		

			



			


		



		<!-- else -->

	</div>
</main>