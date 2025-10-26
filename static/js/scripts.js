// Header Scroll

var $navObj = document.getElementById('nav');
var $navLimit = 18;

document.body.onscroll = function() {
	if( window.scrollY >= $navLimit ){
		$navObj.classList.add('js-fixed');
	} else {
		$navObj.classList.remove('js-fixed');
	}
}

// Functions "borrowed" from this webpage: https://plainjs.com/javascript/utilities/set-cookie-get-cookie-and-delete-cookie-5/

/*function getCookie(name) {
	var v = document.cookie.match('(^|;) ?' + name + '=([^;]*)(;|$)');
	return v ? v[2] : null;
}*/

function setCookie(name, value, days) {
	var d = new Date;
	d.setTime(d.getTime() + 24*60*60*1000*days);
	document.cookie = name + "=" + value + ";path=/;expires=" + d.toGMTString();
}

/*function deleteCookie(name) {
	setCookie(name, '', -1);
}*/

// GDPR Functions

function acceptGDPR() {
	document.cookie = "gdpr=1; expires=Fri, 31 Dec 9999 23:59:59 GMT; path=/"
	
	document.getElementById('gdpr').classList.add('js-hidden');
}

var gdpr = document.getElementById('gdpr-accept')
if( gdpr ){
	gdpr.onclick = acceptGDPR;
}

// Toggle Elements
// Activated via buttons using onclick=""

function toggleElement(id, mode = null) {
	var ele = document.getElementById(id);

	if( mode === null ){
		ele.classList.toggle('u-hidden');
	} else if( mode === true ){
		ele1.classList.remove('u-hidden');
	} else if( mode === false ){
		ele.classList.add('u-hidden');
	}
}

// Toggle Modals
// Activated via buttons using onclick=""

function toggleModal(id, mode = null) {
	var modal = document.getElementById(id);

	if( mode === null ){
		modal.classList.toggle('modal--hidden');
	} else if( mode === true ){
		modal.classList.remove('modal--hidden');
		// set focus to point designated by HTML, if one exists
		var $new_focus = modal.querySelector('.js-modal-focus');
		if( $new_focus ){
			$new_focus.focus();
		}
	} else if( mode === false ){
		modal.classList.add('modal--hidden');
	}
}

// Confirmation Modal

function modalConfirmation($msg, $action, $data = '', $value = '', $post = '/interface/generic') {
	document.getElementById('form-confirmation').setAttribute('action', $post);
	document.getElementById('js-confirmation-msg').innerHTML = $msg;
	document.getElementById('js-confirmation-action').setAttribute('value', $action);
	document.getElementById('js-confirmation-data').setAttribute('name', $data);
	document.getElementById('js-confirmation-data').setAttribute('value', $value);

	toggleModal('modal--confirmation', true);
}

// Autofill Forms

var fills = document.getElementsByClassName('js-autofill');
for( var i = 0; i < fills.length; i++ ){
	var fillEle = fills.item(i),
		fillValue = fillEle.getAttribute('data-autofill');

	if( fillValue !== null ){
		fillEle.value = fillValue;
		fillEle.removeAttribute('data-autofill');
	}
}

function setToday($id) {
	var $today = new Date(),
		$y = new Intl.DateTimeFormat('en', { year: 'numeric' }).format($today),
		$m = new Intl.DateTimeFormat('en', { month: '2-digit' }).format($today),
		$d = new Intl.DateTimeFormat('en', { day: '2-digit' }).format($today),
		$todayFormatted = `${$y}-${$m}-${$d}`;
	document.getElementById($id).value = $todayFormatted;
}

// Search Bar Function

function search( query ){
	window.location.assign('/browse/search?q=' + query);
}

var searchBars = document.getElementsByClassName('js-search-bar');

for( let bar of searchBars ){
	input = bar.getElementsByTagName('input')[0];
	button = bar.getElementsByTagName('button')[0];
	button.addEventListener('pointerup', ev=>{ if( ev.pointerType !== 'mouse' || ev.button === 0 ){ search(input.value); } });
	input.addEventListener('keyup', ev=>{ if( ev.key === 'Enter' ){ search(input.value); } });
}