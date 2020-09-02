// Header Scroll

var $navObj = document.getElementById('nav');
var $navLimit = 18;

document.body.onscroll = function() {
	if(window.scrollY >= $navLimit) {
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
if (gdpr) {
	gdpr.onclick = acceptGDPR;
}

// Toggle Modals
// Activated via buttons using onclick=""

function toggleModal(id, mode = null) {
	var modal = document.getElementById(id);

	if(mode === null) {
		modal.classList.toggle('modal--hidden');
	} else if(mode === true) {
		modal.classList.remove('modal--hidden');
	} else if(mode === false) {
		modal.classList.add('modal--hidden');
	}
}

// Autofill Forms

var fills = document.getElementsByClassName('js-autofill');

for(var i = 0; i < fills.length; i++) {
	var fillEle = fills.item(i),
		fillValue = fillEle.getAttribute('data-autofill');

	if(fillValue !== null) {
		fillEle.value = fillValue;
	}
}