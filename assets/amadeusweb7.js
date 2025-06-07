jQuery(function($){
	const clickToExpand = $('body.mobile-click-to-expand').length;
	if (clickToExpand) {
		$('.primary-menu > ul > li > a')
			.on('click', function(ev) {
				if ($('body').hasClass('is-expanded-menu')) return;
				$('.sub-menu-trigger', $(this).closest('li')).trigger('click');
		});
	}
});
