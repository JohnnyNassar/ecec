(function () {
	'use strict';
	if ( typeof IntersectionObserver === 'undefined' ) { return; }

	function init() {
		var slides = document.querySelectorAll('.ecec-why-slide');
		var items = document.querySelectorAll('.ecec-why-info-item');
		if (!slides.length || !items.length) { return; }

		var setActive = function (idx) {
			items.forEach(function (item) {
				item.classList.toggle('is-active', String(item.dataset.idx) === String(idx));
			});
		};

		var observer = new IntersectionObserver(function (entries) {
			// Pick the entry closest to viewport center among the intersecting ones
			var best = null;
			var viewCenter = window.innerHeight / 2;
			entries.forEach(function (e) {
				if (!e.isIntersecting) { return; }
				var rect = e.target.getBoundingClientRect();
				var slideCenter = rect.top + rect.height / 2;
				var dist = Math.abs(slideCenter - viewCenter);
				if (best === null || dist < best.dist) {
					best = { idx: e.target.dataset.idx, dist: dist };
				}
			});
			if (best) { setActive(best.idx); }
		}, {
			rootMargin: '-30% 0px -30% 0px',
			threshold: [ 0, 0.25, 0.5, 0.75, 1 ]
		});

		slides.forEach(function (s) { observer.observe(s); });
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', init);
	} else {
		init();
	}
})();
