<main id="content" class="wrapper wrapper--about c-about t-light">
	<div class="c-about__section c-about__section--primary">
		<div class="wrapper__inner">
			<div class="c-about__primary">
				<h3 class="c-about__title">Keep track of all your media.</h3>
				<p class="c-about__paragraph">
					<b class="u-bold">No limits.</b>
				</p>
				<p class="c-about__paragraph">
					Create and add media to your collections without going through picky moderators. No more waiting to add a show that doesn't fit any other trackers. 
				</p>
			</div>
		</div>
	</div>



	<div class="c-about__section c-about__section--stats">
		<div class="wrapper__inner">
			<?php
			$stat_users = reset(sql('SELECT COUNT(id) FROM users')['result'][0]);
			$stat_items = reset(sql('SELECT COUNT(id) FROM media')['result'][0]);
			$stat_episodes = reset(sql('
					SELECT SUM(media.episodes)
					FROM media
					INNER JOIN collections
					ON collections.id = media.collection_id
					AND collections.type = "video"')['result'][0]
				);
			$stat_chapters = reset(sql('
					SELECT SUM(media.episodes)
					FROM media
					INNER JOIN collections
					ON collections.id = media.collection_id
					AND collections.type = "literature"')['result'][0]
				);
			$stat_forum_posts = reset(sql('SELECT COUNT(id) FROM threads WHERE deleted=0')['result'][0]);
			$stat_forum_threads = reset(sql('SELECT COUNT(id) FROM replies WHERE deleted=0')['result'][0]);
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

			<!--<h3 class="c-about__title">And become part of the community.</h3>-->
			<div class="c-stats">
				<div class="c-stats__stat c-stats__stat--one-quarter">
					<span class="c-stats__title">Forum Threads</span>
					<span class="c-stats__number">
						<?=$stat_forum_threads?>
					</span>
				</div>
				<div class="c-stats__stat c-stats__stat--one-quarter">
					<span class="c-stats__title">Forum Posts</span>
					<span class="c-stats__number">
						<?=$stat_forum_posts?>
					</span>
				</div>
				<div class="c-stats__stat c-stats__stat--one-quarter">
					<span class="c-stats__title">User Groups</span>
					<span class="c-stats__number">
						Coming Soon
					</span>
				</div>
			</div>
		</div>
	</div>



	<?php if(!$has_session) : ?>
	<div class="c-about__section c-about__section--signup banner--pattern-plaid banner--palette-3"> <!-- TODO - using banner classes is a temp hack, should either change the CSS to be more generic or use something else -->
		<div class="wrapper__inner">
			<div class="c-about__signup">
				<div class="button-list">
					<a class="button-list__button button button--large button--calltoaction">
						Register
					</a>
					<a class="button-list__button button button--large">
						Login
					</a>
				</div>
			</div>
		</div>
	</div>
	<?php endif; ?>
</main>