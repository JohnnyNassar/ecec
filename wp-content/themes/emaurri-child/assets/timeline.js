(function () {
	'use strict';

	function init() {
		var timeline = document.querySelector('.ecec-year-timeline');
		if (!timeline || typeof ECEC_YEAR_MAP !== 'object') return;

		var portfolioList = document.querySelector('.qodef-portfolio-list');
		if (!portfolioList) return;

		// Tag every portfolio article with its project year via data-year
		var articles = Array.prototype.slice.call(
			portfolioList.querySelectorAll('article.qodef-grid-item')
		);
		articles.forEach(function (a) {
			var m = a.className.match(/(?:^|\s)post-(\d+)/);
			if (!m) return;
			var id = m[1];
			if (ECEC_YEAR_MAP[id]) a.setAttribute('data-year', ECEC_YEAR_MAP[id]);
		});

		var coord = window.ECEC_ProjectFilter;

		function applyYear(year) {
			if (coord && coord.ready) {
				coord.state.year = year;
				coord.apply();
				return;
			}
			// Fallback: coordinator missing (shouldn't happen with current enqueue),
			// still perform a minimal year-only pass so the UI isn't broken.
			var cat = '*';
			var active = document.querySelector('.qodef-m-filter-item.qodef--active');
			if (active) cat = active.getAttribute('data-filter') || '*';
			articles.forEach(function (a) {
				var y = a.getAttribute('data-year');
				var yearOk = year === 'all' || y === year;
				var catOk  = cat === '*' || a.classList.contains('portfolio-category-' + cat);
				a.style.display = (yearOk && catOk) ? '' : 'none';
			});
		}

		timeline.addEventListener('click', function (e) {
			var btn = e.target.closest('[data-year]');
			if (!btn) return;
			e.preventDefault();
			var year = btn.getAttribute('data-year');
			timeline.querySelectorAll('[data-year]').forEach(function (el) {
				el.classList.toggle('qodef--active', el === btn);
			});
			applyYear(year);
		});

		// Initial state is already "all"; coordinator runs its own initial apply.
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', init);
	} else {
		init();
	}
})();
