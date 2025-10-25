<?php
$stmt = sql('SELECT COUNT(id) FROM users', [], false);
$total_users = $stmt->rows[0][0];

$pagination = new Pagination();
$pagination->Setup(50, $total_users);

$stmt = sql('
	SELECT id, nickname, profile_image, banner_image, created_at
	FROM users
	ORDER BY nickname
	LIMIT ?, ?',
	['ii', $pagination->offset, $pagination->increment]);
$users = $stmt->rows;
$user_count = $stmt->row_count
?>

<main id="content" class="wrapper wrapper--content">
	<div class="wrapper__inner">
		<div class="content-header">
			<div class="content-header__breadcrumb">
				<a href="/browse">Browse</a> >
				<span>User List</span>
			</div>
			<h2 class="content-header__title">User List</h2>
		</div>

		<?php if( $pagination->total > $pagination->increment ) : ?>
		<div class="page-actions">
			<?php $pagination->Generate(); ?>
		</div>
		<?php endif; ?>

		<div class="c-user-browse">
			<?php
			if( $user_count > 0 ) {
				foreach($users as $module_user) {
					include(PATH.'modules/user_card.php');
				}
			}
			?>
		</div>
	</div>
</main>