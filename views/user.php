<?php
// Check if user is set

/*if(!isset($_GET["id"]) && !isset($_GET["name"]) {
	header('Location: /404');
} elseif(isset($_GET["id"]) && sql($db, "SELECT id FROM users WHERE id=?", "s", $_GET["id"])->num_rows < 1) {
	header('Location: /404');
} elseif(isset($_GET["name"]) && sql($db, "SELECT username FROM users WHERE id=?", "s", $_GET["id"])->num_rows < 1) {
	header('Location: /404');
}
sql($db, "SELECT id, username FROM users WHERE id=?", "s", $_GET["id"])->num_rows < 1*/

if(!isset($_GET["id"])) {
	if($has_session) {
		$page_user__id = $user['id'];
	} else {
		finalize('/404');
	}
} else {
	$page_user__id = $_GET["id"];
}

$page_user = sql('SELECT id, username, nickname, created_at, permission_level, about FROM users WHERE id=?', ['i', $page_user__id]);

if($page_user['rows'] < 1) {
	header('Location: /404');
	exit();
}

$page_user = $page_user['result'][0];
?>

<!-- TODO: Fix classes. Having two BEM blocks layered on top of each other is disgusting -->
<div class="wrapper banner <?php
	$patterns = ['plaid', 'stripes-left', 'stripes-right', 'diamonds', 'half-square'];
	echo "banner--pattern-".$patterns[rand(1,count($patterns)) - 1];
	
	echo " ";

	$palettes = 5;
	echo "banner--palette-".rand(1,$palettes);
?>">
	<div class="wrapper__inner banner__contents">
		<div class="content-header">
			<h2 class="content-header__title">
				<?php $rank = sql('SELECT title FROM permission_levels WHERE permission_level <= ? ORDER BY permission_level DESC LIMIT 1', ['i', $page_user['permission_level']])['result'][0]['title']; ?>
				<?=$page_user['nickname']?>
				<span class="site-rank site-rank--<?=strtolower(str_replace(' ', '-', $rank))?>">
					<?=$rank?>
				</span>
			</h2>
		</div>
	</div>
</div>

<main id="content" class="wrapper wrapper--content">
	<div class="wrapper__inner profile">
		<div class="profile__column profile__column--thin">
			<div class="profile__section">
				<a class="profile__user-link profile__user-link--primary" href="<?=FILEPATH?>collection?user=<?=$page_user['id']?>">Collection</a>
				<a class="profile__user-link profile__user-link--primary" href="<?=FILEPATH?>user/social?user=<?=$page_user['id']?>">Social</a>
				<?php if($has_session && $user['id'] !== $page_user['id']) : ?>
				<a class="profile__user-link profile__user-link--primary">Add Friend</a>
				<a class="profile__user-link" href="<?=FILEPATH?>report?user=<?=$page_user['id']?>">Report</a>
				<?php endif ?>
			</div>

			<div class="profile__section">
				<span class="profile__section-header">Details</span>

				<span title="User since <?=utc_date_to_user($page_user['created_at'])?>.">
					User for <?=readable_date($page_user['created_at'], false)?>
				</span>
			</div>
		</div>
		
		<div class="profile__column profile__column--large">
			<div class="profile__section">
				<span class="profile__section-header">About</span>

				<p class="profile__about">
					<?php if(strlen(trim($page_user['about'])) === 0) : ?>
					This user hasn't told told us about them yet!
					<?php else : ?>
					<?=format_user_text($page_user['about'])?>
					<?php endif; ?>
				</p>
			</div>

			<div class="profile__section">
				<span class="profile__section-header">Activity</span>

				List activity here: began watching... completed...
			</div>
		</div>

		<div class="profile__column profile__column--medium">
			<div class="profile__section">
				<span class="profile__section-header">Stats</span>

				<div class="c-stats">
					<div class="c-stats__stat">
						<span class="c-stats__title">Items</span>
						<span class="c-stats__number">
							<?php
							echo reset(sql('SELECT COUNT(id) FROM media WHERE user_id=?', ['i', $page_user['id']])['result'][0]);
							?>
						</span>
					</div>

					<div class="c-stats__stat">
						<span class="c-stats__title">Episodes CMPL</span>
						<span class="c-stats__number">
							<?php
							$episodes = reset(sql('
								SELECT SUM(media.episodes)
								FROM media
								INNER JOIN collections
								ON collections.id = media.collection_id
								WHERE media.user_id = ?
								AND collections.type = "video"
							', ['i', $page_user['id']])['result'][0]);
							echo round($episodes, 2);
							?>
						</span>
					</div>

					<div class="c-stats__stat">
						<span class="c-stats__title">Chapters CMPL</span>
						<span class="c-stats__number">
							<?php
							$chapters = reset(sql('
								SELECT SUM(media.episodes)
								FROM media
								INNER JOIN collections
								ON collections.id = media.collection_id
								WHERE media.user_id = ?
								AND collections.type = "literature"
							', ['i', $page_user['id']])['result'][0]);
							echo round($chapters, 2);
							?>
						</span>
					</div>

					<div class="c-stats__stat">
						<span class="c-stats__title">Avg. Score</span>
						<span class="c-stats__number">
							<?php
							$avg_score = reset(sql('SELECT AVG(score) FROM media WHERE user_id=? AND score!=0', ['i', $page_user['id']])['result'][0]);
							echo round($avg_score, 2);
							?>
						</span>
					</div>

					<div class="c-stats__stat">
						<span class="c-stats__title">Avg. CMPL Score</span>
						<span class="c-stats__number">
							<?php
							$avg_score = reset(sql('SELECT AVG(score) FROM media WHERE user_id=? AND status="completed" AND score!=0', ['i', $page_user['id']])['result'][0]);
							echo round($avg_score, 2);
							?>
						</span>
					</div>
				</div>
			</div>

			<!-- if favourite > 1 -->
			<div class="profile__section">
				<span class="profile__section-header">Favourites</span>

				<?php
				$favs = sql('SELECT id, name, image FROM media WHERE user_id=? AND favourite=1 ORDER BY name ASC', ['i', $page_user['id']]);
				if($favs['rows'] > 0) :
					foreach($favs['result'] as $fav) :
				?>

				<div><?=$fav['name']?></div>

				<?php
					endforeach;
				else :
				?>

				None yet!

				<?php
				endif;
				?>
			</div>
		</div>
	</div>
</main>