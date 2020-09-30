// Edit Post Show/Hide Functionality
function toggleEdit($reply_id, $mode) {
	var $edit_btn = document.getElementById(`js-edit-reply-${$reply_id}`),
		$body_div = document.getElementById(`js-reply-body-${$reply_id}`),
		$edit_div = document.getElementById(`js-reply-edit-${$reply_id}`);

	if($mode === true) {
		$body_div.style.display = 'none';
		$edit_div.style.display = 'block';
		
		$edit_btn.setAttribute('disabled', '');
		$edit_btn.classList.add('button--disabled');
	} else if($mode === false) {
		$body_div.style.display = 'block';
		$edit_div.style.display = 'none';
		
		$edit_btn.removeAttribute('disabled', '');
		$edit_btn.classList.remove('button--disabled');
	}
}

// Truncate and 'read more' long forum posts
var $body_texts = document.getElementsByClassName(`js-truncate`);

for ($trunc of $body_texts) {
	if($trunc.clientHeight > 500) {
		$id = $trunc.getAttribute('data-reply');
		truncate_reply($id);
	}
}

function truncate_reply($id) {
	var $trunc = document.getElementById(`js-truncate-${$id}`),
		$id = $trunc.getAttribute('data-reply');

	$trunc.classList.add('is-truncated');
	document.getElementById(`js-truncate-overlay-${$id}`).classList.remove('is-hidden');
	document.getElementById(`js-truncate-close-${$id}`).classList.add('is-hidden');
}

function untruncate_reply($id) {
	var $trunc = document.getElementById(`js-truncate-${$id}`),
		$id = $trunc.getAttribute('data-reply');
	
	$trunc.classList.remove('is-truncated');
	document.getElementById(`js-truncate-overlay-${$id}`).classList.add('is-hidden');
	document.getElementById(`js-truncate-close-${$id}`).classList.remove('is-hidden');
}