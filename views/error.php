<?php declare(strict_types=1);
# check current code, which made have been set by another script already
$error = http_response_code();
if( $error === 200 ){
	# if code is 200, then try to get the error from the path.
	# Any non-valid results, such as non-int-like paths, will get caught later with the try/catch
	$error = count(URL['PATH_ARRAY']) > 0 ? intval(URL['PATH_ARRAY'][0]) : 404;
}

# if the path is not a valid error, default to 404
try {
	$response = new HttpResponse($error);
}
catch( ValueError ){
	$response = new HttpResponse(404);
}
http_response_code($response->code);
$page_title = "{$response->code} {$response->message}";
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