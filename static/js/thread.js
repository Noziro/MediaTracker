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