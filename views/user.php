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

<div class="wrapper__inner">
	<div class="user-banner">
		<div class="user-avatar"></div>
	</div>
	
	<div class="page-split">
		<div class="split sidebar">
			<div class="">
				<a class="" href="<?=FILEPATH?>collection?user=<?=$page_user['id']?>">Collection</a>
				<a class="" href="<?=FILEPATH?>report?user=<?=$page_user['id']?>">Report User</a>
			</div>
		</div>
		
		<div class="split mainbar">
			<div class="about">
				<p class="about__text">
					<?php if(strlen(trim($page_user['about'])) === 0) : ?>
					This user hasn't told told us about them yet!
					<?php else : ?>
					<?=format_user_text($page_user['about'])?>
					<?php endif; ?>
				</p>
				
				<br />
				
				<span title="User since <?=utc_date_to_user($page_user['created_at'])?>.">
					User for <?=readable_date($page_user['created_at'], false)?>.
				</span>
				
				<br />
				
				<span class="site-rank">
					<?php
					$rank = sqli_result_bindvar("SELECT title FROM permission_levels WHERE permission_level <= ? ORDER BY permission_level DESC", "s", $page_user["permission_level"]);
					$rank = $rank->fetch_row()[0];
					echo $rank;
					?>
				</span>
			</div>
		</div>
	</div>
</div>