function setCookie(name, value, days) {
	var d = new Date;
	d.setTime(d.getTime() + 24*60*60*1000*days);
	document.cookie = name + "=" + value + ";path=/;expires=" + d.toGMTString();
}

// Uses function set in <head>

document.getElementById('theme-dark').onclick = function(){
	setTheme('dark');
	localStorage.setItem('theme', 'dark');
};
document.getElementById('theme-light').onclick = function(){
	setTheme('light');
	localStorage.setItem('theme', 'light');
};

// GDPR Functions

function acceptGDPR() {
	document.cookie = "gdpr=1; expires=Fri, 31 Dec 9999 23:59:59 GMT; path=/"
	
	document.getElementById('gdpr').classList.add('hidden');
}

document.getElementById('gdpr-accept').onclick = acceptGDPR;