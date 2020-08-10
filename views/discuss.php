<?php

include(PATH . "server/commenting.php");

$commentsQ = mysqli_query($db, "SELECT * FROM comments");
$comments = mysqli_fetch_all($commentsQ, MYSQLI_ASSOC);

$commentsCountQ = mysqli_query($db, "SELECT COUNT(*) AS total FROM comments");
$commentsCount = mysqli_fetch_assoc($commentsCountQ)['total'];

?>

<div class="container">
	<h2>Discuss this project.</h2>
	
	<!-- comments section -->
	<div class="col-md-6 col-md-offset-3 comments-section">
		<!-- comment form -->
		<?php if (isset($user_id)): ?>
			<form class="clearfix" action="post_details.php" method="post" id="comment_form">
				<textarea name="comment_text" id="comment_text" class="form-control" cols="30" rows="3"></textarea>
				<button class="btn btn-primary btn-sm pull-right" id="submit_comment">Submit comment</button>
			</form>
		<?php else: ?>
			<div class="well" style="margin-top: 20px;">
				<h4 class="text-center"><a href="#">Sign in</a> to post a comment</h4>
			</div>
		<?php endif ?>
	
		<!-- Display total number of comments on this post  -->
		<h2><span id="comments_count"><?php echo count($comments) ?></span> Comment(s)</h2>
		<hr>
		<!-- comments wrapper -->
		<div id="comments-wrapper">
			<?php if (isset($comments)): ?>
				<!-- Display comments -->
				<?php foreach ($comments as $comment): ?>
				<!-- comment -->
				<div class="comment clearfix">
					<img src="profile.png" alt="" class="profile_pic">
					<div class="comment-details">
						<span class="comment-name"><?php echo getUsernameById($comment['user_id']) ?></span>
						<span class="comment-date"><?php echo date("F j, Y ", strtotime($comment["created_at"])); ?></span>
						<p><?php echo $comment['body']; ?></p>
					</div>
				</div>
					<!-- // comment -->
				<?php endforeach ?>
			<?php else: ?>
				<h2>Be the first to comment on this post</h2>
			<?php endif ?>
		<!-- // comments wrapper -->
	</div>
	<!-- // comments section -->
</div>