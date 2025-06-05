if (typeof($) === 'undefined') $ = jQuery.noConflict();

$(document).ready(function() {
	$('.toggle-deck-fullscreen').click(toggleDeckFullscreen);
});

//from bells-and-whistles.js
function toggleDeckFullscreen() {
	const link = $(this);
	const full = link.hasClass('is-full-screen');
	$('.text', link).text(full ? 'maximize' : 'minimize');
	const icon = $('.icon', link);

	if (!full) {
		link.addClass('is-full-screen').addClass('icofont-2x')
		icon.removeClass(icon.data('remove'));
		icon.addClass(icon.data('add'));
		$('.deck-container iframe').addClass('is-full-screen');
	} else {
		link.removeClass('is-full-screen').removeClass('icofont-2x')
		icon.addClass(icon.data('remove'));
		icon.removeClass(icon.data('add'));
		$('.deck-container iframe').removeClass('is-full-screen');
	}
}
