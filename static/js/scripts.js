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

document.getElementById('gdpr-accept').onclick = acceptGDPR;