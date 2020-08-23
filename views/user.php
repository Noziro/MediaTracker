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

if(isset($_GET["id"])) {
	$user_page = sqli_result_bindvar("SELECT id, username, nickname, permission_level, created_at FROM users WHERE id=?", "s", $_GET["id"]);
	
	if($user_page->num_rows < 1) {
		header('Location: /404');
		exit();
	}
	
	$user_page = $user_page->fetch_assoc();
} else {
	header('Location: /404');
	exit();
}
?>

<div class="wrapper__inner">
	<div class="user-banner">
		<div class="user-avatar"></div>
	</div>
	
	<div class="page-split">
		<div class="split sidebar">
			<div class="">
				<a class="" href="<?=FILEPATH?>collection?user=<?=$user_page['id']?>">Collection</a>
				<a class="" href="<?=FILEPATH?>report?user=<?=$user_page['id']?>">Report User</a>
			</div>
		</div>
		
		<div class="split mainbar">
			<div class="about">
				<p class="about__text">
					About this user.
				</p>
				
				<br />
				
				<span title="User since <?=utc_date_to_user($user_page['created_at'])?>.">
					User for <?=readable_date($user_page['created_at'], false)?>.
				</span>
				
				<br />
				
				<span class="site-rank">
					<?php
					$rank = sqli_result_bindvar("SELECT title FROM permission_levels WHERE permission_level <= ? ORDER BY permission_level DESC", "s", $user_page["permission_level"]);
					$rank = $rank->fetch_row()[0];
					echo $rank;
					?>
				</span>
			</div>
		</div>
	</div>
</div>