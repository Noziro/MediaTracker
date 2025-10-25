// Edit items functionality
var items = document.getElementsByClassName('js-item-edit'),
	modalName = 'modal--item-edit',
	modalInsert = document.querySelector(`#${modalName} .modal__inner`);

for( var i = 0; i < items.length; i++ ){
	var item = items.item(i);

	item.removeAttribute('href');
}

function editItem(id) {
	var frame = document.getElementById('js-editframe');
	if( frame ){
		frame.remove();
	}

	var frame = document.createElement('iframe');
	frame.setAttribute('src', `/item/${id}/edit?frame=1`);
	modalInsert.appendChild(frame);
	frame.classList.add('modal__frame');
	frame.id = 'js-editframe';

	toggleModal(modalName, true);
}

var addEle = document.getElementById('js-add-input');
addEle.onclick = function(){
	var inputs = this.parentNode.parentNode.querySelectorAll('input[name="links[]"]'),
		input = document.createElement('input');
	input.type = 'text';
	input.className = 'input';
	input.name = 'links[]';
	
	var lastInput = inputs.item(inputs.length - 1);
	lastInput.insertAdjacentElement('afterend', input);

	if( inputs.length === 1 ){
		removeEle.removeAttribute('disabled');
		removeEle.classList.remove('button--disabled');
	}
};

var removeEle = document.getElementById('js-remove-input');
removeEle.onclick = function(){
	var inputs = this.parentNode.parentNode.querySelectorAll('input[name="links[]"]'),
		lastInput = inputs.item(inputs.length - 1);
	
	if( inputs.length === 2 ){
		lastInput.remove();
		removeEle.disabled = 'disabled';
		removeEle.classList.add('button--disabled');
	} else {
		lastInput.remove();
	}
};