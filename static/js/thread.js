// Add Edit Post Show/Hide Functionality

var $editBtns = document.getElementsByClassName('js-edit-reply');

for ($btn of $editBtns) {
	var $replyId = $btn.getAttribute('data-value'),
		$editBtn = document.getElementById('js-edit-reply-'+$replyId),
		$cancelBtn = document.getElementById('js-edit-cancel-'+$replyId);
	
	// Show edit
	$editBtn.onclick = function() {
		var $bodyDiv = document.getElementById('js-reply-body-'+$replyId),
			$editDiv = document.getElementById('js-reply-edit-'+$replyId);
		
		$bodyDiv.style.display = 'none';
		$editDiv.style.display = 'block';
		
		$editBtn.setAttribute('disabled', '');
		$editBtn.classList.add('button--disabled');
	};
	
	// Hide edit
	$cancelBtn.onclick = function() {
		var $bodyDiv = document.getElementById('js-reply-body-'+$replyId),
			$editDiv = document.getElementById('js-reply-edit-'+$replyId);
		
		$bodyDiv.style.display = 'block';
		$editDiv.style.display = 'none';
		
		$editBtn.removeAttribute('disabled');
		$editBtn.classList.remove('button--disabled');
	};
}


