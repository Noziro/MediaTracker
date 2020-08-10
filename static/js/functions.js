// Functions "borrowed" from this webpage: https://plainjs.com/javascript/utilities/set-cookie-get-cookie-and-delete-cookie-5/

export function getCookie(name) {
	var v = document.cookie.match('(^|;) ?' + name + '=([^;]*)(;|$)');
	return v ? v[2] : null;
}

export function setCookie(name, value, days) {
	var d = new Date;
	d.setTime(d.getTime() + 24*60*60*1000*days);
	document.cookie = name + "=" + value + ";path=/;expires=" + d.toGMTString();
}

export function deleteCookie(name) {
	setCookie(name, '', -1);
}