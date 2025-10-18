function incrementSlider() {
	var slider = document.getElementById('js-advance-slider');

	if( slider.getAttribute('style') === null || slider.getAttribute('style') === '' ){
		slider.style.setProperty('--js-slide', 0);
	}

	var slide = parseInt(slider.style.getPropertyValue('--js-slide')) + 1;
	slider.style.setProperty('--js-slide', slide);
}

function decrementSlider() {
	var slider = document.getElementById('js-advance-slider');

	var slide = parseInt(slider.style.getPropertyValue('--js-slide')) - 1;
	slider.style.setProperty('--js-slide', slide);
}