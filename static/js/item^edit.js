var addEle = document.getElementById('js-add-input');
addEle.onclick = function(){
	var inputs = document.querySelectorAll('input[name="links[]"]'),
		input = document.createElement('input');
	input.type = 'text';
	input.className = 'input';
	input.name = 'links[]';
	
	var lastInput = inputs.item(inputs.length - 1);
	lastInput.insertAdjacentElement('afterend', input);

	if(inputs.length === 1) {
		removeEle.removeAttribute('disabled');
		removeEle.classList.remove('button--disabled');
	}
};

var removeEle = document.getElementById('js-remove-input');
removeEle.onclick = function(){
	var inputs = document.querySelectorAll('input[name="links[]"]'),
		lastInput = inputs.item(inputs.length - 1);
	
	if(inputs.length === 2) {
		lastInput.remove();
		removeEle.disabled = 'disabled';
		removeEle.classList.add('button--disabled');
	} else {
		lastInput.remove();
	}
};