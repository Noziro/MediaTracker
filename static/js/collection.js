// Edit items functionality
var items = document.getElementsByClassName('js-item-edit'),
	modalName = 'modal--item-edit',
	modalInsert = document.querySelector(`#${modalName} .modal__inner`);

for(var i = 0; i < items.length; i++) {
	var item = items.item(i);

	item.removeAttribute('href');
}

function editItem(id) {
	var frame = document.getElementById('js-editframe');
	if(frame) {
		frame.remove();
	}

	var frame = document.createElement('iframe');
	frame.setAttribute('src', `/item/edit/${id}&frame=1`);
	modalInsert.appendChild(frame);
	frame.classList.add('modal__frame');
	frame.id = 'js-editframe';

	toggleModal(modalName, true);
}