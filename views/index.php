<?php if($has_session) : ?>

<div class="wrapper__inner">
	<div class="page-split">
		<div class="split mainbar">
			Currently Watching / From Your Lists
			<br /><br />
			(Friend) Activity Feed
			<br /><br />
			More?
			<br /><br />
		</div>
		<div class="split sidebar">
			Popular Now
			<br /><br />
			Recent Announcements
			<br /><br />
			Recent Threads
			<br /><br />
		</div>
	</div>
</div>

<?php else : 

include('about.php');

endif
?>