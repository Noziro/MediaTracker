<!-- if USER SETUP == completed -->

<?php
if(!$has_session) {
	finalize('/');
}
?>

<main id="content" class="wrapper wrapper--content">
	<div class="wrapper__inner">

		Welcome, <?=$user['nickname']?>!
		<br>
		<br>
		Your account has been been created, and you can start collecting! If you wish to personalize your experience please, continue.


		Setup your collections:

			Choose your rating system:



			Import your media from:
			MyAnimeList
			More...


		Setup some basic settings:

			Set about:

			Choose your time zone:



		<!-- else -->

	</div>
</main>