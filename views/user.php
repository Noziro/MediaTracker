<?php
// Check if user is set

/*if(!isset($_GET["id"]) && !isset($_GET["name"]) {
	header('Location: /404');
} elseif(isset($_GET["id"]) && sqli_result_bindvar($db, "SELECT id FROM users WHERE id=?", "s", $_GET["id"])->num_rows < 1) {
	header('Location: /404');
} elseif(isset($_GET["name"]) && sqli_result_bindvar($db, "SELECT username FROM users WHERE id=?", "s", $_GET["id"])->num_rows < 1) {
	header('Location: /404');
}
sqli_result_bindvar($db, "SELECT id, username FROM users WHERE id=?", "s", $_GET["id"])->num_rows < 1*/

if(!isset($_GET["id"])) {
	if($has_session) {
		$page_user__id = $user['id'];
	} else {
		finalize('/404');
	}
} else {
	$page_user__id = $_GET["id"];
}

$page_user = sqli_result_bindvar("SELECT id, username, nickname, created_at, permission_level, about FROM users WHERE id=?", "s", $page_user__id);

if($page_user->num_rows < 1) {
	header('Location: /404');
	exit();
}

$page_user = $page_user->fetch_assoc();
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
				<?=$page_user['nickname']?>
				<?php
				$rank = sqli_result_bindvar("SELECT title FROM permission_levels WHERE permission_level <= ? ORDER BY permission_level DESC", "s", $page_user["permission_level"]);
				$rank = $rank->fetch_row()[0];
				?>
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

				<div>
					Total Items:
					<?php
					$total_items = sqli_result_bindvar('SELECT COUNT(id) FROM media WHERE user_id=?', 'i', $page_user['id']);
					echo $total_items->fetch_row()[0];
					?>
				</div>

				<div>
					Episodes Complete: 
					<?php
					$episodes = sqli_result_bindvar('
						SELECT SUM(media.episodes)
						FROM media
						INNER JOIN collections
						ON collections.id = media.collection_id
						WHERE media.user_id = ?
						AND collections.type = "video"
					', 'i', $page_user['id']);
					$episodes = $episodes->fetch_row()[0];
					echo round($episodes, 2);
					?>
				</div>

				<div>
					Chapters Complete: 
					<?php
					$chapters = sqli_result_bindvar('
						SELECT SUM(media.episodes)
						FROM media
						INNER JOIN collections
						ON collections.id = media.collection_id
						WHERE media.user_id = ?
						AND collections.type = "literature"
					', 'i', $page_user['id']);
					$chapters = $chapters->fetch_row()[0];
					echo round($chapters, 2);
					?>
				</div>

				<div>
					Avg. Score: 
					<?php
					$avg_score = sqli_result_bindvar('SELECT AVG(score) FROM media WHERE user_id=? AND score!=0', 'i', $page_user['id']);
					$avg_score = $avg_score->fetch_row()[0];
					echo round($avg_score, 2);
					?>
				</div>

				<div>
					Avg. Score of Completed: 
					<?php
					$avg_score = sqli_result_bindvar('SELECT AVG(score) FROM media WHERE user_id=? AND status="completed" AND score!=0', 'i', $page_user['id']);
					$avg_score = $avg_score->fetch_row()[0];
					echo round($avg_score, 2);
					?>
				</div>
			</div>

			<!-- if favourite > 1 -->
			<div class="profile__section">
				<span class="profile__section-header">Favourites</span>

				Favourite shows here.
			</div>
		</div>
	</div>
</main>