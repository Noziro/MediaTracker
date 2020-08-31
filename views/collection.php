<div class="wrapper__inner">
	<?php
	if(isset($_GET['id'])) :
		$collection__id = $_GET['id'];
		$collection = sqli_result_bindvar('SELECT id, user_id, name, type FROM collections WHERE id=?', 's', $collection__id);
		$collection = $collection->fetch_assoc();

		$items = sqli_result_bindvar('SELECT id, status, name, score, episodes, user_started_at, user_finished_at, release_date, started_at, finished_at, comments FROM media WHERE collection_id=? ORDER BY name ASC', 's', $collection['id']);
		$items_count = $items->num_rows;
		$items = $items->fetch_all(MYSQLI_ASSOC);

		$page_user = sqli_result_bindvar('SELECT id, nickname FROM users WHERE id=?', 's', $collection['user_id']);
		$page_user = $page_user->fetch_assoc();
		
		$page_user_prefs = sqli_result_bindvar('SELECT rating_system FROM user_preferences WHERE user_id=?', 's', $page_user['id']);
		$page_user_prefs = $page_user_prefs->fetch_assoc();
	?>



	<div class="content-header">
		<div class="content-header__breadcrumb">
			<a href="<?=FILEPATH."user?id=".$page_user['id']?>"><?=$page_user['nickname']?></a> >
			<a href="<?=FILEPATH."collection?user=".$page_user['id']?>">Collection</a> >
			<span><?=$collection['name']?></span>
		</div>
		
		<h2 class="content-header__title"><?=$collection['name']?></h2>
	</div>



	<?php if($has_session && $user['id'] === $page_user['id']) : ?>
	<div class="page-actions">
		<div class="page-actions__button-list">
			<button id="collection-edit" class="page-actions__action button button--disabled" type="button" disabled>
				Edit Collection Details
			</button>
			
			<button id="collection-item-add" class="page-actions__action button button" type="button">
				Add New Items
			</button>
		</div>
	</div>
	<?php endif ?>


	<div class="collection">
		<div class="collection__header">
			<div class="collection__header-column collection__header-column--name">
				Name
			</div>
			
			<div class="collection__header-column">
				Score / <?=$page_user_prefs['rating_system']?>
			</div>
			
			<div class="collection__header-column">
				Episodes
			</div>
			
			<div class="collection__header-column">
				Status
			</div>
			
			<div class="collection__header-column collection__header-column--comments">
				Comments
			</div>
		</div>
		
		<?php
		if($items_count < 1) :
		?>

		<div>No items yet. Add one?</div>

		<?php
		else :
			foreach($items as $item) :
		?>

		<div class="collection__item media">
			<div class="media__column media__column--name">
				<!-- TODO: have this toggle an overlay modal -->
				<a href="item?id=<?=$item['id']?>&frame=1">
					<?=$item['name']?>
				</a>
			</div>

			<div class="media__column">
				<?=score_extrapolate($item['score'], $page_user_prefs['rating_system'])?>
			</div>

			<div class="media__column">
				<?=$item['episodes']?>
			</div>

			<div class="media__column">
				<?=$item['status']?>
			</div>

			<div class="media__column media__column--comments">
				<?=format_user_text($item['comments'])?>
			</div>
		</div>

		<?php
			endforeach;
		endif;
		?>
	</div>



	<!-- TODO: class names & id's & label "for"s -->
	<?php if($has_session && $user['id'] === $page_user['id']) : ?>
	<div id="js-hidetoggle" class="collection-form">
		<form action="/interface" method="POST">
			<input type="hidden" name="action" value="collection-item-create">
			<input type="hidden" name="collection" value="<?=$collection['id']?>">
			
			<label class="label">Name <small>required</small></label>
			<input type="text" name="name" required>
			
			<label class="label">Status</label>
			<select type="text" name="status" required>
				<?php foreach($valid_status as $status) : ?>
				<option <?php if($status === 'planned') { echo "selected"; }?>><?=$status?></option>
				<?php endforeach; ?>
			</select>

			<label class="label">Rating (#/<?=$prefs['rating_system']?>)</label>
			<input name="score" type="number" min="0" max="<?=$prefs['rating_system']?>">

			<label class="label">Episodes</label>
			<input name="episodes" type="number" min="0">

			<label class="label">User Started At</label>
			<input name="user_started_at" type="date">

			<label class="label">User Finished At</label>
			<input name="user_finished_at" type="date">

			<label class="label">Media Release Date</label>
			<input name="release_date" type="date">

			<label class="label">Media Started At</label>
			<input name="started_at" type="date">

			<label class="label">Media Finished At</label>
			<input name="finished_at" type="date">

			<label class="label">Comments</label>
			<textarea name="comments"></textarea>

			<input class="button" type="submit" value="Create">
		</form>
	</div>
	<?php endif; ?>





	<?php
	// If user is not specified, redirect to own page.
	elseif(!isset($_GET['user']) && $has_session || isset($_GET['user'])) :
		if(!isset($_GET['user'])) {
			$page_user__id = $user['id'];
		} else {
			$page_user__id = $_GET['user'];
		}

		$page_user = sqli_result_bindvar('SELECT id, nickname FROM users WHERE id=?', 's', $page_user__id);
		$page_user = $page_user->fetch_assoc();

		$collections = sqli_result_bindvar('SELECT id, user_id, name, type, private FROM collections WHERE user_id=? AND deleted=FALSE ORDER BY name ASC', 's', $page_user['id']);
		$collections_count = $collections->num_rows;
		$collections = $collections->fetch_all(MYSQLI_ASSOC);
	?>



	<div class="content-header">
		<div class="content-header__breadcrumb">
			<a href="<?=FILEPATH."user?id=".$page_user['id']?>"><?=$page_user['nickname']?></a> >
			<span>Collection</span>
		</div>
		
		<h2 class="content-header__title"><?=$user['nickname']?>'s Collection</h2>
	</div>



	<?php if($has_session) : ?>
	<div class="page-actions">
		<div class="page-actions__button-list">
			<?php if($user['id'] === $page_user['id']) : ?>

			<button id="collection-create" class="page-actions__action button" type="button">
				New Collection
			</button>

			<?php else : ?>

			<button id="" class="page-actions__action button button--disabled" type="button" disabled>
				Compare Collections
			</button>

			<?php endif ?>
		</div>
	</div>
	<?php endif ?>



	<div class="collection-group">
		<?php
		if($collections_count < 1) :
		?>

		<div>No collections yet. Create one?</div>

		<?php
		else :
			foreach($collections as $collection) :
		?>

		<div class="collection-group__row">
			<a class="collection-group__name" href="?id=<?=$collection['id']?>">
				<?=$collection['name']?>
			</a>
			<span class="collection-group__type">
				<?=$collection['type']?>
			</span>
			<?php if($collection['private'] === 9) : ?>
			<b>
				Private
			</b>
			<?php endif; ?>
		</div>

		<?php
			endforeach;
		endif;
		?>
	</div>



	<!-- TODO: class names -->
	<div id="js-hidetoggle" class="forum-submit">
		<form action="/interface" method="POST">
			<input type="hidden" name="action" value="collection-create">
			
			<label class="label" for="collection-name">Name</label>
			<input id="collection-name" class="forum-submit__title" type="text" name="name" required>
			
			<label class="label" for="collection-type">Type</label>
			<select id="collection-type" name="type">
				<?php foreach($valid_coll_types as $type) : ?>
				<option><?=$type?></option>
				<?php endforeach; ?>
			</select>

			<label class="label" for="collection-privacy">Privacy</label>
			<select id="collection-privacy" name="private">
				<option value="0">Public</option>
				<option value="9">Only Me</option>
			</select>

			<input class="forum-submit__button button" type="submit" value="Create">
		</form>
	</div>





	<?php
	else :
		header('Location: /404');
		exit();
	
	endif;
	?>
</div>