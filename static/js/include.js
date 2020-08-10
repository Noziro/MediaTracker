// Theme Selector

function setTheme(theme) {
	var classes = document.documentElement.classList;
	
	for (var cls of classes) {
		if (cls.startsWith('theme-')) {
			classes.remove(cls);
		}
	}
	
	classes.add('theme-' + theme);
}

if(localStorage.getItem('theme') !== null) {
	setTheme(localstorage.getItem('theme'));
}