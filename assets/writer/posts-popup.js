jQuery(document).ready(function() {
	jQuery('#post-area .open-post').click(openPost);
});

function openPost() {
	var link = $(this);

	var text = jQuery('<div class="post-text"></div>');
	var post = link.closest('.pinbin-copy');
	post.append(text);
	var url = post.data('popup-url');

	jQuery.ajax(url).done(function (txt){
		jQuery(text).append(txt);
		jQuery(link).hide();
	});
}

