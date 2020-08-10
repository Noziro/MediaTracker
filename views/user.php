<?php
// Check if user is set

/*if(!isset($_GET["id"]) && !isset($_GET["name"]) {
	header('Location: /404');
} elseif(isset($_GET["id"]) && sqli_result($db, "SELECT id FROM users WHERE id=?", "s", $_GET["id"])->num_rows < 1) {
	header('Location: /404');
} elseif(isset($_GET["name"]) && sqli_result($db, "SELECT username FROM users WHERE id=?", "s", $_GET["id"])->num_rows < 1) {
	header('Location: /404');
}
sqli_result($db, "SELECT id, username FROM users WHERE id=?", "s", $_GET["id"])->num_rows < 1*/

if(isset($_GET["id"])) {
	$user_page = sqli_result("SELECT id, username, nickname, permission_level, created_at FROM users WHERE id=?", "s", $_GET["id"]);
	
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

<div class="container">
	<div class="user-banner">
		<div class="user-avatar"></div>
	</div>
	
	<div class="page-split">
		<div class="split sidebar">
			<div class="list vertical">
				<a class="item link" href="<?=FILEPATH?>collection?user=<?=$page_owner_id?>">Collection</a>
				<a class="item link" href="<?=FILEPATH?>report?user=<?=$page_owner_id?>">Report User</a>
			</div>
		</div>
		
		<div class="split mainbar">
			<div class="about">
				About this user.
				
				<span class="site-rank">
					<?php
					$rank = sqli_result("SELECT title FROM permission_levels WHERE permission_level <= ? ORDER BY permission_level DESC", "s", $user_page["permission_level"]);
					$rank = $rank->fetch_row()[0];
					echo $rank;
					?>
				</span>
			</div>
		</div>
	</div>
</div>