/**
 * ECEC Projects page — unified filter coordinator.
 * Reads state from the search bar, year timeline, and theme's category sidebar;
 * shows/hides articles as their intersection. Each source calls apply().
 */
(function () {
	'use strict';

	var api = {
		state: { search: '', type: '', location: '', year: 'all' },
		ready: false,
		apply: function () {}  // wired up in init(); noop until then
	};
	window.ECEC_ProjectFilter = api;

	function init() {
		var portfolioList = document.querySelector('.qodef-portfolio-list');
		if (!portfolioList) return;

		var articles = Array.prototype.slice.call(
			portfolioList.querySelectorAll('article.qodef-grid-item')
		);
		if (!articles.length) return;

		// Cache lower-cased title for fast search matching.
		// Location/category are read directly from theme-applied classes
		// (portfolio-location-<slug>, portfolio-category-<slug>).
		articles.forEach(function (a) {
			var titleEl = a.querySelector('.qodef-e-title, h4, h5, h6, .qodef-grid-item-title');
			var title = titleEl ? titleEl.textContent : a.textContent;
			a.setAttribute('data-search-title', (title || '').trim().toLowerCase());
		});

		function getActiveCategory() {
			var active = document.querySelector('.qodef-m-filter-item.qodef--active');
			return active ? (active.getAttribute('data-filter') || '*') : '*';
		}

		function matchesSidebarCategory(article, cat) {
			if (cat === '*' || !cat) return true;
			return article.classList.contains('portfolio-category-' + cat);
		}

		function matchesTypeDropdown(article, type) {
			if (!type) return true;
			return article.classList.contains('portfolio-category-' + type);
		}

		function matchesLocation(article, loc) {
			if (!loc) return true;
			return article.classList.contains('portfolio-location-' + loc);
		}

		function matchesYear(article, year) {
			if (!year || year === 'all') return true;
			return article.getAttribute('data-year') === String(year);
		}

		function matchesSearch(article, q) {
			if (!q) return true;
			var t = article.getAttribute('data-search-title') || '';
			return t.indexOf(q) !== -1;
		}

		function apply() {
			var sidebar = getActiveCategory();
			var s = api.state;
			articles.forEach(function (a) {
				var ok = matchesSidebarCategory(a, sidebar)
					&& matchesTypeDropdown(a, s.type)
					&& matchesLocation(a, s.location)
					&& matchesYear(a, s.year)
					&& matchesSearch(a, s.search);
				a.style.display = ok ? '' : 'none';
			});
		}
		api.apply = apply;
		api.ready = true;

		// Wire up the search bar
		var form = document.querySelector('.ecec-project-search');
		if (form) {
			var input = form.querySelector('.ecec-ps-input');
			var typeSel = form.querySelector('[data-role="type"]');
			var locSel  = form.querySelector('[data-role="location"]');
			var btn     = form.querySelector('[data-role="search"]');

			if (input) {
				input.addEventListener('input', function () {
					api.state.search = this.value.trim().toLowerCase();
					apply();
				});
			}
			if (typeSel) {
				typeSel.addEventListener('change', function () {
					api.state.type = this.value;
					apply();
				});
			}
			if (locSel) {
				locSel.addEventListener('change', function () {
					api.state.location = this.value;
					apply();
				});
			}
			if (btn) {
				btn.addEventListener('click', apply);
			}
		}

		// Theme sidebar category — re-apply after theme toggles its own active class
		var categoryFilter = document.querySelector('.qodef-m-filter');
		if (categoryFilter) {
			categoryFilter.addEventListener('click', function (e) {
				if (!e.target.closest('.qodef-m-filter-item')) return;
				setTimeout(apply, 30);
			});
		}

		apply();
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', init);
	} else {
		init();
	}
})();
