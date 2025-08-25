/**
 * v2 of the icon explorer feature from "bhava"
 * 
 * DO NOTE: This is proprietary software by Imran Ali Namazi.
 * It cannot be reused, distributed or derived without
 * prior written consent after paying a royalty for it.
 */

if (typeof($) === 'undefined') $ = jQuery.noConflict();

$(document).ready(function() {
	const pageVars = {
		'itemCount': 0,
		'itemsShown': 0,
	}

	initializeExplorer();
	$('#prefix').on('change', initializeExplorer);
	$('#search').on('change', filterItems);

	function updateCounts() {
		$('#counts').val(pageVars.itemsShown + ' of ' + pageVars.itemCount);
	}

	function initializeExplorer() {
		const div = $('#icons');
		const prefix = $('#prefix').val();
		const magnify = $('#magnify').val();
		const items = getAllSelectors('.' + prefix);
		
		pageVars.itemsShown = pageVars.itemCount = items.length;
		updateCounts();
		
		items.forEach(function (item) {
			$('<div class="col-md-3 col-sm-6" />')
				.append('<span class="' + magnify + ' '
					+ item + '"><br />'
					+ item.replaceAll(prefix, '').replaceAll('-', ' ')
					+ '</span>').appendTo(div);
		});
	};

	function filterItems() {
		const searchVal = $('#search').val();
		let shown = 0;

		$('#icons span').each(function() {
			const span = $(this);
			const div = span.closest('div');

			if (searchVal == '' || span.text().includes(searchVal)) {
				shown += 1;
				div.show();
			} else {
				div.hide();
			} 
		});

		pageVars.itemsShown = shown;
		updateCounts();
	}

	function getAllSelectors(prefix) {
		const ret = [];

		for(var i = 0; i < document.styleSheets.length; i++) {
			const sheet = document.styleSheets[i];
			//if (sheet.ownerNode && sheet.ownerNode.id != 'current') continue;
			if (!sheet.href || !sheet.href.includes('icons')) continue;

			const rules = sheet.rules || sheet.cssRules;

			for(var x in rules) {
				const txt = rules[x].selectorText;
				if(typeof txt == 'string'){
					if (txt.startsWith(prefix))
						ret.push(txt.replaceAll('.', '').replaceAll('::before', '').replaceAll('::after', ''));
				} 
			}
		}

		return ret;
	}
});
