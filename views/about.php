<main id="content" class="wrapper wrapper--about c-about t-light">
	<div class="c-about__section c-about__section--primary">
		<div class="wrapper__inner">
			<div class="c-about__primary">
				<h1 class="c-about__opener"><?=SITE_NAME?></h1>
				<h3 class="c-about__title">Track your media without limits.</h3>
			</div>
		</div>
	</div>



	<div class="c-about__section c-about__section--generic-2">
		<div class="wrapper__inner">
			<div class="c-about__split">
				<div class="c-about__split-primary">
					<h3 class="c-about__title">You're in control.</h3>
					<p class="c-about__paragraph">
						Create and add media without going through moderators. No more waiting to track that one web comic or anime that doesn't fit on other websites.  
					</p>
				</div>
				<div class="c-about__split-secondary">

				</div>
			</div>
		</div>
	</div>



	<div class="c-about__section c-about__section--stats">
		<div class="wrapper__inner">
			<?php
			$stmt = sql('SELECT COUNT(id) FROM users', [], ['assoc' => false]);
			$stat_users = $stmt['result'][0][0] ?? 0;

			$stmt = sql('SELECT COUNT(id) FROM media', [], ['assoc' => false]);
			$stat_items = $stmt['result'][0][0] ?? 0;
			
			$stmt = sql('
					SELECT SUM(media.progress)
					FROM media
					INNER JOIN collections
					ON collections.id = media.collection_id
					WHERE collections.type = "video"
					AND collections.deleted = 0
					AND media.deleted = 0
				', [], ['assoc' => false]);
			$stat_episodes = $stmt['result'][0][0] ?? 0;
			
			$stmt = sql('
					SELECT SUM(media.progress)
					FROM media
					INNER JOIN collections
					ON collections.id = media.collection_id
					WHERE collections.type = "literature"
					AND collections.deleted = 0
					AND media.deleted = 0
				', [], ['assoc' => false]);
			$stat_chapters = $stmt['result'][0][0] ?? 0;
			?>

			<h3 class="c-about__title">Join <?=$stat_users?> others in tracking your favourite media.</h3>
			<div class="c-stats">
				<div class="c-stats__stat c-stats__stat--one-quarter">
					<span class="c-stats__title">Total Users</span>
					<span class="c-stats__number">
						<?=$stat_users?>
					</span>
				</div>
				<div class="c-stats__stat c-stats__stat--one-quarter">
					<span class="c-stats__title">Items Collected</span>
					<span class="c-stats__number">
						<?=$stat_items?>
					</span>
				</div>
				<div class="c-stats__stat c-stats__stat--one-quarter">
					<span class="c-stats__title">Episodes Watched</span>
					<span class="c-stats__number">
						<?=$stat_episodes?>
					</span>
				</div>
				<div class="c-stats__stat c-stats__stat--one-quarter">
					<span class="c-stats__title">Chapters Read</span>
					<span class="c-stats__number">
						<?=$stat_chapters?>
					</span>
				</div>
			</div>
		</div>
	</div>



	<?php if(!$has_session) : ?>
	<div class="c-about__section c-about__section--signup banner--pattern-plaid banner--palette-3"> <!-- TODO - using banner classes is a temp hack, should either change the CSS to be more generic or use something else -->
		<div class="wrapper__inner">
			<div class="c-about__signup">
				<div class="l-button-list">
					<a class="l-button-list__button button button--large button--calltoaction" href="/register">
						Register
					</a>
					<a class="l-button-list__button button button--large" href="/login">
						Login
					</a>
				</div>
			</div>
		</div>
	</div>
	<?php endif; ?>
</main>