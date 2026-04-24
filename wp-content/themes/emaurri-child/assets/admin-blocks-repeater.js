/**
 * ECEC single-project blocks — admin repeater UI.
 *
 * Source of truth: the hidden <textarea name="ecec_blocks_json"> that the
 * existing PHP save handler reads. On load, parse its JSON into `state`; on
 * every edit, serialize `state` back into the textarea. PHP then sanitizes
 * each block on save — the JS is presentation only.
 */
(function ($, wp) {
	'use strict';

	var BLOCK_TYPES = [
		{ key: 'text-paragraph',   label: 'Text Paragraph' },
		{ key: 'full-image',       label: 'Full-width Image' },
		{ key: 'image-pair',       label: 'Image Pair (side by side)' },
		{ key: 'image-text-split', label: 'Image + Text (split)' },
		{ key: 'project-data',     label: 'Project Data Table' },
		{ key: 'pull-quote',       label: 'Pull Quote' },
		{ key: 'gallery',          label: 'Gallery' }
	];

	var BLOCK_DEFAULTS = {
		'text-paragraph':   { body: '' },
		'full-image':       { image_id: 0, caption: '' },
		'image-pair':       { image_id_left: 0, image_id_right: 0, caption_left: '', caption_right: '' },
		'image-text-split': { image_id: 0, overline: '', heading: '', body: '', image_side: 'left' },
		'project-data':     { overline: 'PROJECT DATA', heading: 'At a glance', rows: [{ label: '', value: '' }] },
		'pull-quote':       { quote: '', attribution: '' },
		'gallery':          { image_ids: [], columns: 3 }
	};

	var attachmentCache = $.extend({}, (window.ECEC_BLOCKS_BOOT || {}).attachments || {});

	var state = [];
	var $textarea, $root, $list;

	$(function () {
		$textarea = $('#ecec_blocks_json');
		$root     = $('#ecec-blocks-repeater');
		if (!$textarea.length || !$root.length) { return; }

		state = parseState($textarea.val());
		$list = $root.find('.ecec-blocks-list');

		renderAll();
		wireEvents();
	});

	function parseState(raw) {
		if (!raw || !String(raw).trim()) { return []; }
		try {
			var parsed = JSON.parse(raw);
			return Array.isArray(parsed) ? parsed : [];
		} catch (e) {
			console.warn('ECEC blocks: invalid JSON in textarea, starting empty.', e);
			return [];
		}
	}

	function syncState() {
		$textarea.val(JSON.stringify(state, null, 2));
	}

	// -- Rendering ----------------------------------------------------------

	function renderAll() {
		$list.empty();
		state.forEach(function (block, idx) {
			var $card = renderCard(block, idx);
			$list.append($card);
			initSubSortables($card, idx);
		});
		$list.sortable({
			handle: '.ecec-block-handle',
			items: '> .ecec-block-card',
			axis: 'y',
			tolerance: 'pointer',
			placeholder: 'ecec-block-placeholder',
			update: onBlockReorder
		});
		updateEmptyState();
	}

	function onBlockReorder() {
		var newState = [];
		$list.children('.ecec-block-card').each(function () {
			var oldIdx = parseInt($(this).attr('data-index'), 10);
			newState.push(state[oldIdx]);
		});
		state = newState;
		syncState();
		renderAll();
	}

	function updateEmptyState() {
		$root.find('.ecec-blocks-empty').toggle(state.length === 0);
	}

	function renderCard(block, idx) {
		var typeDef = BLOCK_TYPES.find(function (t) { return t.key === block.type; });
		var label = typeDef ? typeDef.label : block.type;

		var $card = $('<div class="ecec-block-card" data-index="' + idx + '"></div>');
		$card.append(
			'<div class="ecec-block-head">' +
				'<span class="ecec-block-handle dashicons dashicons-menu" title="Drag to reorder"></span>' +
				'<span class="ecec-block-label">' + escapeHtml(label) + '</span>' +
				'<span class="ecec-block-summary">' + escapeHtml(blockSummary(block)) + '</span>' +
				'<button type="button" class="button-link ecec-block-toggle" aria-expanded="false">Edit</button>' +
				'<button type="button" class="button-link ecec-block-duplicate" title="Duplicate this block">Duplicate</button>' +
				'<button type="button" class="button-link-delete ecec-block-delete" title="Remove block">&times;</button>' +
			'</div>'
		);
		var $body = $('<div class="ecec-block-body"></div>');
		$body.html(renderFields(block));
		$card.append($body);

		return $card;
	}

	function blockSummary(block) {
		switch (block.type) {
			case 'text-paragraph':
				return truncate(stripTags(block.body || ''), 80) || '(empty)';
			case 'full-image':
				return block.image_id ? 'Image set' : '(no image)';
			case 'image-pair':
				return (block.image_id_left ? '✓ left' : '— left') + ' · ' + (block.image_id_right ? '✓ right' : '— right');
			case 'image-text-split':
				return block.heading || truncate(stripTags(block.body || ''), 60) || '(empty)';
			case 'project-data':
				var n = (block.rows || []).length;
				return (block.heading || 'Data') + ' — ' + n + ' row' + (n === 1 ? '' : 's');
			case 'pull-quote':
				return truncate(stripTags(block.quote || ''), 60) || '(empty)';
			case 'gallery':
				var g = (block.image_ids || []).length;
				return g + ' image' + (g === 1 ? '' : 's');
			default:
				return '';
		}
	}

	function renderFields(block) {
		switch (block.type) {
			case 'text-paragraph':
				return field('Body (blank lines = paragraphs · HTML allowed: &lt;strong&gt; &lt;em&gt; &lt;a&gt; &lt;br&gt;)', textarea('body', block.body, 6));
			case 'full-image':
				return field('Image', singleImage('image_id', block.image_id))
					+ field('Caption (optional)', textInput('caption', block.caption));
			case 'image-pair':
				return field('Left image', singleImage('image_id_left', block.image_id_left))
					+ field('Left caption (optional)', textInput('caption_left', block.caption_left))
					+ field('Right image', singleImage('image_id_right', block.image_id_right))
					+ field('Right caption (optional)', textInput('caption_right', block.caption_right));
			case 'image-text-split':
				return field('Image', singleImage('image_id', block.image_id))
					+ field('Image position', selectInput('image_side', block.image_side || 'left', [['left', 'Image on left'], ['right', 'Image on right']]))
					+ field('Overline (small-caps line above heading)', textInput('overline', block.overline))
					+ field('Heading', textInput('heading', block.heading))
					+ field('Body (blank lines = paragraphs · HTML allowed)', textarea('body', block.body, 5));
			case 'project-data':
				return field('Overline', textInput('overline', block.overline || 'PROJECT DATA'))
					+ field('Heading', textInput('heading', block.heading))
					+ field('Rows (drag to reorder)', rowsEditor(block.rows || []));
			case 'pull-quote':
				return field('Quote', textarea('quote', block.quote, 4))
					+ field('Attribution (Name, Title)', textInput('attribution', block.attribution));
			case 'gallery':
				return field('Images (drag thumbnails to reorder · × to remove)', galleryEditor(block.image_ids || []))
					+ field('Columns', selectInput('columns', String(block.columns || 3), [['2', '2 columns'], ['3', '3 columns']]));
			default:
				return '<em>Unknown block type: ' + escapeHtml(block.type) + '</em>';
		}
	}

	// -- Field builders -----------------------------------------------------

	function field(labelHtml, innerHtml) {
		return '<div class="ecec-field"><label class="ecec-field-label">' + labelHtml + '</label>' + innerHtml + '</div>';
	}

	function textInput(name, value) {
		return '<input type="text" class="regular-text ecec-field-input" data-field="' + name + '" value="' + escapeAttr(value || '') + '">';
	}

	function textarea(name, value, rows) {
		return '<textarea class="large-text ecec-field-input" data-field="' + name + '" rows="' + (rows || 4) + '">' + escapeHtml(value || '') + '</textarea>';
	}

	function selectInput(name, value, options) {
		var html = '<select class="ecec-field-input" data-field="' + name + '">';
		options.forEach(function (opt) {
			var selected = String(opt[0]) === String(value) ? ' selected' : '';
			html += '<option value="' + escapeAttr(opt[0]) + '"' + selected + '>' + escapeHtml(opt[1]) + '</option>';
		});
		return html + '</select>';
	}

	function singleImage(name, imageId) {
		var id = parseInt(imageId, 10) || 0;
		var cached = id && attachmentCache[id] ? attachmentCache[id] : null;
		var html = '<div class="ecec-image-picker" data-picker="single" data-field="' + name + '">';
		if (cached) {
			html += '<div class="ecec-image-picker__preview">'
				+ '<img class="ecec-image-picker__thumb" src="' + escapeAttr(cached.thumb) + '" alt="">'
				+ '<div class="ecec-image-picker__meta">#' + id + (cached.title ? ' · ' + escapeHtml(cached.title) : '') + '</div>'
				+ '</div>'
				+ '<div class="ecec-image-picker__actions">'
					+ '<button type="button" class="button ecec-image-pick">Change image</button> '
					+ '<button type="button" class="button-link-delete ecec-image-clear">Remove</button>'
				+ '</div>';
		} else {
			html += '<button type="button" class="button ecec-image-pick">Select image</button>';
			if (id) {
				html += ' <span class="ecec-image-missing">attachment #' + id + ' not found</span>';
			}
		}
		return html + '</div>';
	}

	function galleryEditor(imageIds) {
		var ids = (imageIds || []).map(function (n) { return parseInt(n, 10) || 0; }).filter(Boolean);
		var html = '<div class="ecec-gallery-editor">';
		html += '<ul class="ecec-gallery-thumbs">';
		ids.forEach(function (id) {
			var cached = attachmentCache[id];
			html += '<li class="ecec-gallery-thumb" data-id="' + id + '">';
			if (cached) {
				html += '<img src="' + escapeAttr(cached.thumb) + '" alt="">';
			} else {
				html += '<span class="ecec-gallery-thumb__placeholder">#' + id + '</span>';
			}
			html += '<button type="button" class="ecec-gallery-remove" title="Remove">&times;</button>';
			html += '</li>';
		});
		html += '</ul>';
		html += '<button type="button" class="button ecec-gallery-add">Add images</button>';
		return html + '</div>';
	}

	function rowsEditor(rows) {
		var html = '<ul class="ecec-rows">';
		rows.forEach(function (row, ri) {
			html += '<li class="ecec-row" data-row-index="' + ri + '">'
				+ '<span class="ecec-row-handle dashicons dashicons-menu" title="Drag to reorder"></span>'
				+ '<input type="text" class="ecec-row-label ecec-field-input" data-field="label" data-row-index="' + ri + '" placeholder="Label (e.g. GFA)" value="' + escapeAttr(row.label || '') + '">'
				+ '<input type="text" class="ecec-row-value ecec-field-input" data-field="value" data-row-index="' + ri + '" placeholder="Value (e.g. 56,000 sqm)" value="' + escapeAttr(row.value || '') + '">'
				+ '<button type="button" class="button-link-delete ecec-row-remove" title="Remove row">&times;</button>'
				+ '</li>';
		});
		html += '</ul>';
		return html + '<button type="button" class="button ecec-row-add">Add row</button>';
	}

	// -- Events -------------------------------------------------------------

	function wireEvents() {
		// Expand/collapse card
		$root.on('click', '.ecec-block-toggle', function () {
			var $card = $(this).closest('.ecec-block-card');
			var expanded = $card.toggleClass('is-expanded').hasClass('is-expanded');
			$(this).text(expanded ? 'Close' : 'Edit').attr('aria-expanded', expanded ? 'true' : 'false');
		});

		// Remove block
		$root.on('click', '.ecec-block-delete', function () {
			var idx = cardIdx(this);
			if (!confirm('Remove this block?')) { return; }
			state.splice(idx, 1);
			syncState();
			renderAll();
		});

		// Duplicate block (deep-clone and insert immediately after)
		$root.on('click', '.ecec-block-duplicate', function () {
			var idx = cardIdx(this);
			var clone = deepClone(state[idx]);
			state.splice(idx + 1, 0, clone);
			syncState();
			renderAll();
			var $newCard = $list.children('.ecec-block-card[data-index="' + (idx + 1) + '"]');
			$newCard.addClass('is-expanded')
				.find('.ecec-block-toggle').text('Close').attr('aria-expanded', 'true');
			if ($newCard[0] && $newCard[0].scrollIntoView) {
				$newCard[0].scrollIntoView({ behavior: 'smooth', block: 'center' });
			}
		});

		// Text/select field changes
		$root.on('input change', '.ecec-field-input', function () {
			var $card = $(this).closest('.ecec-block-card');
			var idx = parseInt($card.attr('data-index'), 10);
			var fieldName = $(this).attr('data-field');
			var rowIdxAttr = $(this).attr('data-row-index');
			var val = $(this).val();
			if (fieldName === 'columns') { val = parseInt(val, 10) === 2 ? 2 : 3; }
			if (rowIdxAttr !== undefined) {
				state[idx].rows = state[idx].rows || [];
				state[idx].rows[parseInt(rowIdxAttr, 10)] = state[idx].rows[parseInt(rowIdxAttr, 10)] || {};
				state[idx].rows[parseInt(rowIdxAttr, 10)][fieldName] = val;
			} else {
				state[idx][fieldName] = val;
			}
			syncState();
			updateCardSummary($card, idx);
		});

		// Single image pick
		$root.on('click', '.ecec-image-pick', function () {
			var idx = cardIdx(this);
			var fieldName = $(this).closest('.ecec-image-picker').attr('data-field');
			openMediaPicker({ multiple: false, title: 'Select image' }, function (attachments) {
				if (!attachments.length) { return; }
				var a = attachments[0];
				state[idx][fieldName] = a.id;
				cacheAttachment(a);
				syncState();
				rerenderCard(idx);
			});
		});

		// Single image clear
		$root.on('click', '.ecec-image-clear', function () {
			var idx = cardIdx(this);
			var fieldName = $(this).closest('.ecec-image-picker').attr('data-field');
			state[idx][fieldName] = 0;
			syncState();
			rerenderCard(idx);
		});

		// Gallery: add
		$root.on('click', '.ecec-gallery-add', function () {
			var idx = cardIdx(this);
			openMediaPicker({ multiple: true, title: 'Select gallery images' }, function (attachments) {
				if (!attachments.length) { return; }
				state[idx].image_ids = Array.isArray(state[idx].image_ids) ? state[idx].image_ids : [];
				attachments.forEach(function (a) {
					state[idx].image_ids.push(a.id);
					cacheAttachment(a);
				});
				syncState();
				rerenderCard(idx);
			});
		});

		// Gallery: remove single thumbnail
		$root.on('click', '.ecec-gallery-remove', function () {
			var idx = cardIdx(this);
			var id = parseInt($(this).closest('.ecec-gallery-thumb').attr('data-id'), 10);
			state[idx].image_ids = (state[idx].image_ids || []).filter(function (x) {
				return parseInt(x, 10) !== id;
			});
			syncState();
			rerenderCard(idx);
		});

		// Rows: add
		$root.on('click', '.ecec-row-add', function () {
			var idx = cardIdx(this);
			state[idx].rows = Array.isArray(state[idx].rows) ? state[idx].rows : [];
			state[idx].rows.push({ label: '', value: '' });
			syncState();
			rerenderCard(idx);
		});

		// Rows: remove
		$root.on('click', '.ecec-row-remove', function () {
			var idx = cardIdx(this);
			var ri = parseInt($(this).closest('.ecec-row').attr('data-row-index'), 10);
			state[idx].rows.splice(ri, 1);
			syncState();
			rerenderCard(idx);
		});

		// Add block
		$root.on('click', '.ecec-add-block-btn', function () {
			var type = $root.find('.ecec-add-block-type').val();
			if (!type || !BLOCK_DEFAULTS[type]) { return; }
			var newBlock = $.extend({ type: type }, deepClone(BLOCK_DEFAULTS[type]));
			state.push(newBlock);
			syncState();
			renderAll();

			var $newCard = $list.children('.ecec-block-card').last();
			$newCard.addClass('is-expanded')
				.find('.ecec-block-toggle').text('Close').attr('aria-expanded', 'true');
			if ($newCard[0] && $newCard[0].scrollIntoView) {
				$newCard[0].scrollIntoView({ behavior: 'smooth', block: 'center' });
			}
		});
	}

	function rerenderCard(idx) {
		var $old = $list.children('.ecec-block-card[data-index="' + idx + '"]');
		var wasExpanded = $old.hasClass('is-expanded');
		var $new = renderCard(state[idx], idx);
		if (wasExpanded) {
			$new.addClass('is-expanded')
				.find('.ecec-block-toggle').text('Close').attr('aria-expanded', 'true');
		}
		$old.replaceWith($new);
		initSubSortables($new, idx);
	}

	function initSubSortables($card, idx) {
		var $thumbs = $card.find('.ecec-gallery-thumbs');
		if ($thumbs.length) {
			$thumbs.sortable({
				items: '> li',
				tolerance: 'pointer',
				update: function () {
					var newIds = [];
					$thumbs.children('li').each(function () {
						newIds.push(parseInt($(this).attr('data-id'), 10));
					});
					state[idx].image_ids = newIds;
					syncState();
					updateCardSummary($card, idx);
				}
			});
		}
		var $rows = $card.find('.ecec-rows');
		if ($rows.length) {
			$rows.sortable({
				items: '> li',
				handle: '.ecec-row-handle',
				tolerance: 'pointer',
				update: function () {
					var newRows = [];
					$rows.children('li').each(function (newIdx) {
						var oldIdx = parseInt($(this).attr('data-row-index'), 10);
						newRows.push(state[idx].rows[oldIdx]);
					});
					state[idx].rows = newRows;
					syncState();
					// Reindex DOM so subsequent edits target the right row
					$rows.children('li').each(function (newIdx) {
						$(this).attr('data-row-index', newIdx);
						$(this).find('[data-row-index]').attr('data-row-index', newIdx);
					});
					updateCardSummary($card, idx);
				}
			});
		}
	}

	function updateCardSummary($card, idx) {
		$card.find('.ecec-block-summary').first().text(blockSummary(state[idx]));
	}

	// -- Media modal --------------------------------------------------------

	function openMediaPicker(opts, onSelect) {
		var frame = wp.media({
			title: opts.title || 'Select image',
			button: { text: 'Use this' },
			library: { type: 'image' },
			multiple: opts.multiple ? 'add' : false
		});
		frame.on('select', function () {
			var selection = frame.state().get('selection').toJSON();
			onSelect(selection);
		});
		frame.open();
	}

	function cacheAttachment(a) {
		var thumb = (a.sizes && a.sizes.thumbnail && a.sizes.thumbnail.url)
			|| (a.sizes && a.sizes.medium && a.sizes.medium.url)
			|| a.url;
		attachmentCache[a.id] = { thumb: thumb, title: a.title || '' };
	}

	// -- Utils --------------------------------------------------------------

	function cardIdx(el) {
		return parseInt($(el).closest('.ecec-block-card').attr('data-index'), 10);
	}

	function escapeHtml(s) {
		return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) {
			return ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[c];
		});
	}

	function escapeAttr(s) { return escapeHtml(s); }

	function stripTags(s) { return String(s == null ? '' : s).replace(/<[^>]*>/g, ''); }

	function truncate(s, n) {
		s = String(s == null ? '' : s).trim();
		return s.length > n ? s.slice(0, n - 1) + '…' : s;
	}

	function deepClone(o) { return JSON.parse(JSON.stringify(o)); }

})(jQuery, window.wp);
