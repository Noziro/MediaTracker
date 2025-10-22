<?php
$response = new HttpResponse(intval(URL['PATH_ARRAY'][0]));
http_response_code($response->code);
?>

<main id="content" class="wrapper wrapper--content c-page-failure">
	<div class="wrapper__inner c-page-failure__inner">
		<h1 class="c-page-failure__title"><?=$response->code?></h1>
		<h4 class="c-page-failure__subtitle"><?=$response->message?></h4>
		<?php if( strlen($response->details) > 0 ) : ?>
		<p class="c-page-failure__description"><?=$response->details?></p>
		<?php endif; ?>
	</div>
</main>