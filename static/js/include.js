// Theme Selector

function setTheme(theme) {
	var classes = document.documentElement.classList;
	
	for( var cls of classes ){
		if( cls.startsWith('t-') ){
			classes.remove(cls);
		}
	}
	
	classes.add('t-' + theme);
}

function selectTheme(theme) {
	console.log(theme);
	setTheme(theme);
	localStorage.setItem('theme', theme);
}

if( window.localStorage.getItem('theme') !== null ){
	setTheme(localStorage.getItem('theme'));
}