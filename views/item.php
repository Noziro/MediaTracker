<?php

if( count(URL['PATH_ARRAY']) < 2 ){
	bailout('/404');
}

$item_id = URL['PATH_ARRAY'][1];
if( !preg_eval('/\d+/', $item_id) ){
	bailout('/404');
}
$item_id = intval($item_id);

$stmt = sql('SELECT id, user_id, collection_id, image, name, episodes, release_date, started_at, finished_at, links, adult, private, deleted FROM media WHERE id=? LIMIT 1', ['i', $item_id]);
if( !$stmt->ok || $stmt->row_count === 0 ){
	bailout('/404');
}
$item = $stmt->rows[0];

if( $item['deleted'] === 1 ){
	bailout('/404');
}

if($item['private'] === 1 && !$has_session ||
   $item['private'] === 1 && $user['id'] !== $item['user_id']) {
	bailout('/403');
}

$stmt = sql('SELECT id, user_id, name, type, private FROM collections WHERE id=?', ['s', $item['collection_id']]);
if( !$stmt->ok || $stmt->row_count === 0 ){
	bailout('/404');
}
$collection = $stmt->rows[0];
?>

<main id="content" class="wrapper wrapper--content">
	<div class="wrapper__inner l-split">
		<?php if(!empty($item['image'])) : ?>
		<div class="l-split__section l-split__section--image">
			<img class="c-mega-image" src="<?=$item['image']?>" />
		</div>
		<?php endif; ?>

		<div class="l-split__section">
			<div class="content-header">
				<div class="content-header__breadcrumb">
					<a href="/browse">Browse</a> >
					<span><?=$item['name']?></span>
				</div>
			
				<h2 class="content-header__title">
					<?=$item['name']?>
				</h2>

				<?php if($item['adult'] === 1) : ?>
				<h6 class="content-header__subtitle">Adult</h6>
				<?php endif; ?>
			</div>

			<div class="l-rows l-leave-a-gap">
				<div class="c-module">
					<h6 class="c-heading-minor">Episodes</h6>
					<?=$item['episodes']?>
				</div>

				<div class="c-module">
					<h6 class="c-heading-minor">Date Released</h6>
					<?php
					# TODO: change this display depending on the media type and/or episode count
					# TODO: this date code is a god damn crime against human society
					$any_date_set = !empty($item['started_at']) || !empty($item['finished_at']);
					$both_dates_set = !empty($item['started_at']) && !empty($item['finished_at']);
					if( $any_date_set ){
						$start_date = $item['started_at'] !== null ? new DateTime($item['started_at']) : null;
						$end_date = $item['finished_at'] !== null ? new DateTime($item['finished_at']) : null;
						$start_str = $item['started_at'] !== null ? $start_date->format('Y-M-d') : 'Unknown';
						$end_str = $item['finished_at'] !== null ? $end_date->format('Y-M-d') : 'Ongoing';
						/*if( $item['started_at'] === $item['finished_at'] ){
							echo $item['started_at'];
						}
						else {
							echo $start_str . ' -> ' . $end_str;
						}*/

						echo '<div class="c-date-range">';

						if( $both_dates_set ){
							$offset_start = -1;
							$extra_points = 2;
						}
						elseif( isset($start_date) ){
							$offset_start = -1;
							$extra_points = 1;
						}
						elseif( isset($end_date) ){
							$offset_start = 0;
							$extra_points = 2;
						}

						$start_year = isset($start_date) ? intval($start_date->format('Y')) : 0;
						$end_year = isset($end_date) ? intval($end_date->format('Y')) : 0;
						$year_diff = $both_dates_set ? $end_year - $start_year : 0;

						for( $i = $offset_start; $i < $year_diff + $extra_points; $i++ ){
							$year = isset($start_date) ? $start_year + $i : $end_year + $i;
							$long_line = $i === $offset_start && $start_year === 0 ? 'c-date-range__line--long' : '';
							$line_selected = $year > $start_year && $year <= $end_year ? 'c-date-range__line--selected' : '';
							echo '<div class="c-date-range__line '.$line_selected.' '.$long_line.'"></div>';
							$dot_selected = '';
							if( $both_dates_set && $year >= $start_year && $year <= $end_year ||
								isset($start_date) && $year === $start_year ||
								isset($end_date) && $year === $end_year ){
								
								$dot_selected = 'c-date-range__dot--selected';
							}
							echo '<div class="c-date-range__dot '.$dot_selected.'" data-dot="'.$year.'"></div>';
						}
						$long_line = $end_year === 0 ? 'c-date-range__line--long c-date-range__line--selected' : '';
						echo '<div class="c-date-range__line ' . $long_line . '"></div>';

						echo '</div>';
					}
					else {
						echo 'Unknown';
					}

					?>
				</div>
			</div>
			
			<div class="l-leave-a-gap">
				<h6 class="c-heading-minor">From the library of...</h6>
				<?php
				$stmt = sql('SELECT u.id, u.nickname, u.created_at, u.profile_image, u.banner_image FROM users AS u INNER JOIN media AS m ON m.user_id = u.id WHERE u.id=? LIMIT 1', ['i', $item['user_id']]);
				$module_user = $stmt->rows[0]; include(PATH.'modules/user_card.php');
				?>

				<?php if( $has_session ) : ?>
				<div class="page-actions">
					<div class="page-actions__button-list">
						<?php if( $collection['user_id'] === $user['id'] ) : ?>
						<a class="page-actions__action button" href="/item/<?=$item['id']?>/edit?return_to=/item/<?=$item['id']?>">
							Edit
						</a>
						<?php endif; ?>
						<a class="page-actions__action button" href="/item/add?from=<?=$item['id']?>&return_to=/item/<?=$item['id']?>">
							Clone
						</a>
					</div>
				</div>
				<?php endif; ?>
			</div>
		</div>
		


		<?php
		$links = json_decode($item['links']);
		if($links !== null && count($links) !== 0) : ?>

		<br />

		<div>
			Links:<br />

			<?php
			foreach( $links as $link ){
				echo '<a href="'.$link.'">'.$link.'</a><br />';
			}
			?>
		</div>

		<?php endif; ?>
	</div>
</main>