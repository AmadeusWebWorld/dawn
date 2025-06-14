/*
 * Designed in March 2022 as part of Amadeus CMS 3, for imran.yieldmore.org
 * Copyright: Imran Ali Namazi - https://amadeus.yieldmore.org/license/
 * 		 i.e: Source Available and Proprietary
 */

jQuery(document).ready(function() {
	jQuery('#search .search-text').on('change, keyup', searchInBrowser);
});

function searchInBrowser() {
	var searchText = $('.search-text', scope).val().toLowerCase();
	var searchResults = $('#search-results');
	searchResults.html('');
	if (searchText.length < 3) return;

	var scope = $('#search');
	var all =  $('.search-in-all', scope).is(':checked');
	var searchIn = window.searchIn = {
		titles: all || $('.search-in-titles', scope).is(':checked'),
		descriptions: all || $('.search-in-descriptions', scope).is(':checked'),
		filters: all || $('.search-in-filters', scope).is(':checked'),
	};

	var searchables = [];
	if (searchIn.titles) searchables.push('.searchable-title');
	if (searchIn.descriptions) searchables.push('.searchable-description');
	if (searchIn.filters) searchables.push('.searchable-filter');

	var searchMatches = $(searchables.join(', '), $('.searchable-content')).filter(function(index, item) {
		return $(item).text().toLowerCase().indexOf(searchText) !== -1; 
	});

	$.each(searchMatches, function(index, el) {
		var item = $(el);
		var link = $('<a href="javascript: void();" />').addClass(item.data('match')).text(item.text()).data('search-target', el).click(searchMatchClick);
		searchResults.append(link);
	});
}

function searchMatchClick() {
	var target = $(this).data('search-target');
	if (window.searchIn.filters) $(target).addClass('search-clicked');
	target.scrollIntoView();
}
