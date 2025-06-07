jQuery(function($){
	const clickToExpand = $('body.mobile-click-to-expand').length;
	if (clickToExpand) {
		$('.primary-menu > ul > li > a')
			.on('click', function(ev) {
				if ($('body').hasClass('is-expanded-menu')) return;
				$('.sub-menu-trigger', $(this).closest('li')).trigger('click');
		});
	}

	//styling in v8! - needs cleanup / reorg
	const navigable = $('.navigable');
	if (navigable.length && location.hash) {
		//highlight= is explicit and doesnt mess with the header offset
		const matched = navigable.filter('[name="' + location.hash.substr(1).replaceAll('highlight=', '') + '"]');
		matched.closest('tr, li').addClass('navigated-to');
	}
});
