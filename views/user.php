<?php
// Check if user is set

/*if( !isset($_GET["u"]) && !isset($_GET["n"] ){
	header('Location: /404');
} elseif( isset($_GET["u"]) && sql($db, "SELECT id FROM users WHERE id=?", "s", $_GET["u"])->num_rows < 1 ){
	header('Location: /404');
} elseif( isset($_GET["name"]) && sql($db, "SELECT username FROM users WHERE id=?", "s", $_GET["u"])->num_rows < 1 ){
	header('Location: /404');
}
sql($db, "SELECT id, username FROM users WHERE id=?", "s", $_GET["u"])->num_rows < 1*/

if( !isset($_GET["u"]) ){
	if( $has_session ){
		$page_user__id = $user['id'];
	} else {
		finalize('/404');
	}
} else {
	$page_user__id = $_GET["u"];
}

$page_user = sql('SELECT id, username, nickname, created_at, permission_level, profile_image, banner_image, about FROM users WHERE id=?', ['i', $page_user__id]);

if( $page_user->row_count < 1 ){
	finalize('/404');
}

$page_user = $page_user->rows[0];

$stmt = sql('SELECT profile_colour FROM users WHERE id=?', ['i', $page_user['id']]);
if( $stmt->row_count > 0 ){
	$page_user_prefs = $stmt->rows[0];
}
else {
	$page_user_prefs = null;
}

if( isset($user['id']) && $user['id'] === $page_user['id'] ){
	$friendship = 9;
} else {
	$friendship = 0;
}

// This is a clusterfuck but for now it has to do
$total_activity = reset(sql('
	SELECT COUNT(activity.user_id)
	FROM activity
	INNER JOIN media ON activity.media_id = media.id
	INNER JOIN collections ON media.collection_id = collections.id
	WHERE activity.user_id=? AND media.private <= ? AND collections.private <= ?',
	['iii', $page_user['id'], $friendship, $friendship])->rows[0]);

$pg = new Pagination();
$pg->Setup(10, $total_activity);
$activity = sql('
	SELECT activity.user_id, activity.user_id, activity.type, activity.media_id, activity.created_at, activity.updated_at, media.private AS media_private, collections.private AS collection_private
	FROM activity
	INNER JOIN media ON activity.media_id = media.id
	INNER JOIN collections ON media.collection_id = collections.id
	WHERE activity.user_id=? AND media.private <= ? AND collections.private <= ?
	ORDER BY created_at DESC
	LIMIT ?, ?',
	['iiiii', $page_user['id'], $friendship, $friendship, $pg->offset, $pg->increment]);


function ceil_decimal(float $float, int $precision = 1) {
	if( $precision === 0 ){
		return ceil($float);
	} else {
		$precision *= 10;
		return ceil($float * $precision) / $precision;
	}
}

?>

<!-- TODO: Fix classes. Having two BEM blocks layered on top of each other is disgusting -->
<div class="wrapper banner <?php
if( empty($page_user['banner_image']) ){
	$patterns = ['plaid', 'stripes-left', 'stripes-right', 'diamonds', 'half-square'];
	echo "banner--pattern-".$patterns[rand(1,count($patterns)) - 1];
	
	echo " ";

	$palettes = 5;
	echo "banner--palette-".rand(1,$palettes);
	echo '"';
} else {
	echo '" style="background-image:url(';

	echo $page_user['banner_image'];

	echo ');"';
}
?>>
	<div class="wrapper__inner banner__contents">
		<?php if(!empty($page_user['profile_image'])) : ?>
		<img class="profile-image" src="<?=$page_user['profile_image']?>">
		<?php endif; ?>

		<div class="content-header">
			<h2 class="content-header__title">
				<?php
				$rank = 'User';
				$rank_query = sql('SELECT title FROM permission_levels WHERE permission_level <= ? ORDER BY permission_level DESC LIMIT 1', ['i', $page_user['permission_level']]);
				if( $rank_query->row_count > 0 ){
					$rank = $rank_query->rows[0]['title'];
				}
				?>
				<?=$page_user['nickname']?>
				<span class="site-rank site-rank--<?=strtolower(str_replace(' ', '-', $rank))?>">
					<?=$rank?>
				</span>
			</h2>
		</div>
	</div>
</div>

<main id="content" class="wrapper wrapper--content">
	<div class="wrapper__inner profile" <?php if( $page_user_prefs !== null && isset($page_user_prefs['profile_colour']) ){ echo 'style="--profile-colour: '.$page_user_prefs['profile_colour'].'"'; } ?>>
		<div class="profile__column profile__column--thin">
			<div class="profile__section">
				<span class="profile__section-header">Links</span>

				<a class="profile__user-link profile__user-link--primary" href="/collection?u=<?=$page_user['id']?>">Collection</a>
				<!-- TODO: List friends here -->
				<?php if($has_session && $user['id'] !== $page_user['id']) : ?>
				<div class="c-divider"></div>
				<a class="profile__user-link">Add Friend</a>
				<a class="profile__user-link" href="/report?user=<?=$page_user['id']?>">Report</a>
				<?php endif; ?>
			</div>

			<div class="profile__section">
				<span class="profile__section-header">Details</span>

				<span title="User since <?=utc_date_to_user($page_user['created_at'])?>.">
					User for <?=readable_date($page_user['created_at'], false)?>
				</span>
			</div>

			<div class="profile__section">
				<span class="profile__section-header">History</span>

				<?php if($activity->row_count > 0) : ?>
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
						if( $history->row_count > 0 ){
							foreach( $history->rows as $row ){
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
								', ['i', $page_user['id']])->rows[0]['max'];
						}
						
						$last_month = [];
						for( $i = 0; $i < 90; $i++ ){
							$last_month[] = date('Y-m-d', strtotime('-'.$i.' days'));
						}

						foreach($last_month as $day) :

						if( array_key_exists($day, $history_flattened) ){
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
				<?php $pg->Generate() ?>
				
				<span class="profile__section-header">Activity</span>

				<?php
				$i = 0;
				if($activity->row_count > 0) :
					foreach($activity->rows as $activity_item) :
						if( $activity_item['media_private'] > $friendship || $activity_item['collection_private'] > $friendship ){
							continue;
						}
						if(isset($activity_item['media_id'])) :
							$stmt = sql('SELECT media.id, media.collection_id, media.name, media.progress FROM media WHERE media.id=?', ['i', $activity_item['media_id']]);
							$item = $stmt->rows[0];
							if( !$stmt->ok ){
								continue;
							}

						$i += 1;
				?>

				<div class="c-activity">
					<?php
					if($activity_item['type'] > 0 && $activity_item['type'] < 6) :
					?>

					<div class="c-activity__header">
						<?php
						switch($activity_item['type']) {
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
						<a href="/item?id=<?=$item['id']?>"><?=$item['name']?></a>
					</div>

					<?php endif; ?>

					<div class="c-activity__date">
						<?=readable_date($activity_item['created_at'])?>
					</div>

					<?php if($activity_item['media_private'] > 0 || $activity_item['collection_private'] > 0) : ?>
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

				<?php

				$stmt = sql('
						SELECT COUNT(id)
						FROM media
						WHERE user_id = ?
					', ['i', $page_user['id']], false);
				$stat_total = $stmt->rows[0][0] ?? 0;

				$stmt = sql('
						SELECT SUM(media.progress + media.rewatched)
						FROM media
						INNER JOIN collections
						ON collections.id = media.collection_id
						WHERE media.user_id = ?
						AND collections.type = "video"
					', ['i', $page_user['id']], false);
				$stat_episodes = $stmt->rows[0][0] ?? 0;

				$stmt = sql('
						SELECT SUM(media.progress + media.rewatched)
						FROM media
						INNER JOIN collections
						ON collections.id = media.collection_id
						WHERE media.user_id = ?
						AND collections.type = "literature"
					', ['i', $page_user['id']], false);
				$stat_chapters = $stmt->rows[0][0] ?? 0;

				$stmt = sql('
						SELECT SUM(media.rewatched) / SUM(media.progress)
						FROM media
						WHERE user_id = ?
					', ['i', $page_user['id']], false);
				$stat_rewatch = round(100 * $stmt->rows[0][0] ?? 0, 2);

				$stmt = sql('
						SELECT AVG(score)
						FROM media
						WHERE user_id = ?
						AND score != 0
					', ['i', $page_user['id']], false);
				$stat_avg_score = round($stmt->rows[0][0] ?? 0, 2);

				?>

				<div class="c-stats">
					<div class="c-stats__stat">
						<span class="c-stats__title">Items</span>
						<span class="c-stats__number">
							<?=$stat_total?>
						</span>
					</div>

					<div class="c-stats__stat">
						<span class="c-stats__title">Episodes Watched</span>
						<span class="c-stats__number">
							<?=$stat_episodes?>
						</span>
					</div>

					<div class="c-stats__stat">
						<span class="c-stats__title">Chapters Read</span>
						<span class="c-stats__number">
							<?=$stat_chapters?>
						</span>
					</div>

					<div class="c-stats__stat">
						<span class="c-stats__title">% Media Rewatched</span>
						<span class="c-stats__number">
							<?=$stat_rewatch?>%
						</span>
					</div>

					<div class="c-stats__stat">
						<span class="c-stats__title">Avg. Score</span>
						<span class="c-stats__number">
							<?=$stat_avg_score?>
						</span>
					</div>
				</div>
			</div>

			<div class="profile__section">
				<span class="profile__section-header">Favourites</span>

				<?php
				$favs = sql('SELECT media.id, media.name, media.image FROM media INNER JOIN collections ON collections.id = media.collection_id WHERE media.user_id=? AND media.favourite=1 AND collections.private <= ? ORDER BY name ASC', ['ii', $page_user['id'], $friendship]);
				if($favs->row_count > 0) :
					foreach($favs->rows as $fav) :
				?>

				<div><a href="/item?id=<?=$fav['id']?>"><?=$fav['name']?></a></div>

				<?php
					endforeach;
				else :
				?>

				<div class="dialog-box dialog-box--subcontent">
					None yet.
				</div>

				<?php endif; ?>
			</div>
		</div>
	</div>
</main>