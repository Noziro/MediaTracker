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

$page_user_prefs = sql('SELECT profile_colour FROM user_preferences WHERE user_id=?', ['i', $page_user['id']])['result'][0];

if($user['id'] === $page_user['id']) {
	$friendship = 9;
} else {
	$friendship = 0;
}

$activity = sql('SELECT user_id, type, media_id, body, created_at, updated_at FROM activity WHERE user_id=? ORDER BY created_at DESC', ['i', $page_user['id']]);

function ceil_decimal(float $float, int $precision = 1) {
	if($precision === 0) {
		return ceil($float);
	} else {
		$precision *= 10;
		return ceil($float * $precision) / $precision;
	}
}

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
	<div class="wrapper__inner profile" <?php if($page_user_prefs['profile_colour'] !== null) { echo 'style="--profile-colour: '.$page_user_prefs['profile_colour'].'"'; } ?>>
		<div class="profile__column profile__column--thin">
			<div class="profile__section">
				<span class="profile__section-header">Links</span>

				<a class="profile__user-link profile__user-link--primary" href="<?=FILEPATH?>collection?user=<?=$page_user['id']?>">Collection</a>
				<a class="profile__user-link profile__user-link--primary" href="<?=FILEPATH?>user/social?user=<?=$page_user['id']?>">Social</a>
				<?php if($has_session && $user['id'] !== $page_user['id']) : ?>
				<div class="c-divider"></div>
				<a class="profile__user-link">Add Friend</a>
				<a class="profile__user-link" href="<?=FILEPATH?>report?user=<?=$page_user['id']?>">Report</a>
				<?php endif ?>
			</div>

			<div class="profile__section">
				<span class="profile__section-header">Details</span>

				<span title="User since <?=utc_date_to_user($page_user['created_at'])?>.">
					User for <?=readable_date($page_user['created_at'], false)?>
				</span>
			</div>

			<div class="profile__section">
				<span class="profile__section-header">History</span>

				<?php if($activity['rows'] > 0) : ?>
				<div class="c-user-history">
					<div class="c-user-history__block-wrap">
						<?php
						$history = sql('
								SELECT
									DATE(created_at) as day,
									COUNT(user_id) as count
								FROM activity
								WHERE
									user_id=?
									AND DATE(created_at) BETWEEN DATE_SUB(CURDATE(), INTERVAL 90 DAY) AND CURDATE()
								GROUP BY day
								ORDER BY day DESC
							', ['i', $page_user['id']]);
						$history_flattened = [];
						foreach($history['result'] as $row) {
							$history_flattened[$row['day']] = $row['count'];
						}
						$history_max_num = sql('
								SELECT MAX(count) as max
								FROM
								(
									SELECT
										DATE(created_at) as day,
										COUNT(user_id) as count
									FROM activity
									WHERE
										user_id=?
										AND DATE(created_at) BETWEEN DATE_SUB(CURDATE(), INTERVAL 90 DAY) AND CURDATE()
									GROUP BY day
									ORDER BY day DESC
								) as sub
							', ['i', $page_user['id']])['result'][0]['max'];
						
						$last_month = [];
						for($i = 0; $i < 90; $i++) {
							$last_month[] = date('Y-m-d', strtotime('-'.$i.' days'));
						}

						foreach($last_month as $day) :

						if(array_key_exists($day, $history_flattened))  {
							// get opacity. checks level in relation to max
							$count = $history_flattened[$day];
							$opacity = ceil_decimal($count / $history_max_num, 1);
						} else {
							$count = 0;
						}
						?>

						<div class="c-user-history__block" title="<?=$count?> updates on <?=utc_date_to_user($day, false)?>">
							<?php if($count > 0) : ?>
							<div class="c-user-history__block-colour" style="opacity: <?=$opacity?>">
								
							</div>
							<?php endif; ?>
						</div>

						<?php endforeach; ?>
					</div>
				</div>

				<?php else : ?>

				<div class="dialog-box dialog-box--subcontent">
					None yet.
				</div>

				<?php endif; ?>
			</div>
		</div>
		
		<div class="profile__column profile__column--large">
			<?php if(strlen(trim($page_user['about'])) !== 0) : ?>
			<div class="profile__section">
				<span class="profile__section-header">About</span>

				<p class="profile__about">
					<?=format_user_text($page_user['about'])?>
				</p>
			</div>
			<?php endif; ?>

			<div class="profile__section">
				<span class="profile__section-header">Activity</span>

				<!-- TODO - need pagination here -->

				<?php
				$i = 0;
				if($activity['rows'] > 0) :
					foreach($activity['result'] as $activity) :
						if(isset($activity['media_id'])) :
							$stmt = sql('SELECT media.id, media.collection_id, media.name, media.progress, collections.private FROM media INNER JOIN collections ON media.collection_id = collections.id WHERE media.id=?', ['i', $activity['media_id']]);
							$item = $stmt['result'][0];
							if(!$stmt['result'] || $item['private'] > $friendship) {
								continue;
							}

						$i += 1;
				?>

				<div class="c-activity">
					<?php
					if($activity['type'] > 0 && $activity['type'] < 6) :
					?>

					<div class="c-activity__header">
						<?php
						switch($activity['type']) {
								case 1:
									echo 'Started ';
									break;
								case 2:
									echo 'Completed ';
									break;
								case 3:
									echo 'Paused ';
									break;
								case 4:
									echo 'Dropped ';
									break;
								case 5:
									echo 'Plans to start ';
									break;
							}
						?>
						<?=$item['name']?>
					</div>

					<?php
					endif;
					?>

					<div class="c-activity__date">
						<?=readable_date($activity['created_at'])?>
					</div>

					<?php
					if($activity['body'] !== '') :
					?>
					
					<div class="c-activity__body">
						<?=$activity['body']?>
					</div>

					<?php
					endif;
					?>

					<?php if($item['private'] > 0) : ?>
					<div class="c-activity__actions">
						<span class="c-activity__tag">
							Private
						</span>
					</div>
					<?php endif; ?>
				</div>

				<?php
						endif;
					endforeach;
				endif;
				
				if($i === 0) :
				?>

				<div class="dialog-box dialog-box--subcontent">
					None yet.
				</div>

				<?php
				endif;
				?>
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
						<span class="c-stats__title">Episodes Watched</span>
						<span class="c-stats__number">
							<?php
							$episodes = reset(sql('
								SELECT SUM(media.progress)
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
						<span class="c-stats__title">Chapters Read</span>
						<span class="c-stats__number">
							<?php
							$chapters = reset(sql('
								SELECT SUM(media.progress)
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

			<div class="profile__section">
				<span class="profile__section-header">Favourites</span>

				<?php
				$favs = sql('SELECT media.id, media.name, media.image FROM media INNER JOIN collections ON collections.id = media.collection_id WHERE media.user_id=? AND media.favourite=1 AND collections.private <= ? ORDER BY name ASC', ['ii', $page_user['id'], $friendship]);
				if($favs['rows'] > 0) :
					foreach($favs['result'] as $fav) :
				?>

				<div><?=$fav['name']?></div>

				<?php
					endforeach;
				else :
				?>

				<div class="dialog-box dialog-box--subcontent">
					None yet.
				</div>

				<?php
				endif;
				?>
			</div>
		</div>
	</div>
</main>