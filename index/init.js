(function () {

	skel.init({
		reset: 'full',
		breakpoints: {
			'global': {range: '*', href: 'index/style.css', viewport: {scalable: false}},
			'wide': {range: '-1680', href: 'index/style-wide.css'},
			'normal': {range: '-1280', href: 'index/style-normal.css'},
			'mobile': {range: '-736', href: 'index/style-mobile.css'},
			'mobilep': {range: '-480', href: 'index/style-mobilep.css'}
		}
	});
	window.onload = function () {
		document.body.className = '';
	};

	window.ontouchmove = function () {
		return false;
	};

	window.onorientationchange = function () {
		document.body.scrollTop = 0;
	}

})();
