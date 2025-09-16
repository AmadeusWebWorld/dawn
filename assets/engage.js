/**
 * v2.6 of the engage feature - a part of dawn.amadeusweb.com (v8.5)
 *     .5 - make it more fluent / friendly.
 * 	   .6 - supports salutation
 * **** Get Emails with details on each call to action.
 * 
 * DO NOTE: This is proprietary software by Imran Ali Namazi.
 * It cannot be reused, distributed or derived without
 * prior written consent after paying a royalty for it.
 */

if (typeof($) === 'undefined') $ = jQuery.noConflict();

$(document).ready(function() {
	$('textarea.autofit').on('input', textAreaAutoHeight).trigger('input');
	var divs = $('.engage');
	if (divs.length == 0) return;

	function safeScrollWithOffset(element) {
		if (window.amadeusUtils)
			window.amadeusUtils.scrollWithOffset(element);
		else
			element.scrollIntoView();
	}

	$('.engage li').each(checkboxAdd);

	divs.each(function(ix, div) {
		div = $(div);
		$('textarea', div).on('change, input', textAreaAutoHeight);
		$('.prepare-message', div).click(prepareMessage);
	});

	$('.toggle-engage').click(function() {
		const targetId = $(this).data('engage-target');

		$('.engage:not(#' + targetId + ')').hide();
		const target = $('#' + targetId);

		if ($(this).hasClass('engage-scroll')) {
			target.show();
			safeScrollWithOffset(target[0]);
		} else {
			target.toggle();
		}
	});


	function prepareMessage() {
		var div = $(this).closest('.engage');

		var messageBox = $('textarea.message-content', div);
		var hiddenLinks = $('.action-wrapper .d-none', div);
		var disabledLinks = $('.action-wrapper .disabled', div);

		var items = $('input[type=checkbox]:checked, textarea:not(.draft-message):is("visible")', div);
		if (items.length == 0) {
			messageBox.text('No Items Ticked');
			messageBox.removeClass('d-none');
			return;
		}
		
		const siteName = div.data('site-name');
		let salutation = div.data('salutation');
		if (!salutation) salutation = 'Dear Amadeus Web World,';
		var headings = {}, firstHeading = true, output = salutation + "\r\n\r\n";

		items.each(function() {
			var item = $(this).closest('li');
			var note = $('input[type=text], textarea', item);
			var ul = item.closest('ul, ol');
			var hx = ul.prevAll('h2:first, h3:first').text();
			if (!headings[hx]) {
				if (!firstHeading) output += "\r\n\r\n\r\n";
				firstHeading = false;
				output += "# " + hx;
				headings[hx] = true;
			}
			output += "\r\n\r\n" + item.text() + "\r\n -> " + note.val();
		});

		const caseId = URL.createObjectURL(new Blob()).substr(-6).toUpperCase();
		const date_r = new Date().toDateString();
		output += "\r\n\r\nRegards,\r\n --- [Me]\r\n\r\n[Case Number:"
			+ caseId + ", Date Raised: " + date_r + "]";
		
		messageBox.removeClass('d-none').text(output).trigger('input');
		hiddenLinks.removeClass('d-none');
		disabledLinks.removeClass('disabled');
		prepareMessageLinks(output, div, caseId, siteName);
	}

	function prepareMessageLinks(message, div, caseId, siteName) {
		var emailTo = div.data('to');
		var emailCc = div.data('cc');

		var email = emailTo.replace(';', '%3B%20');

		var user = $('.sender-name', div).val();

		var subject = '[%caseId%] "%user%" responds on website: %site%'
				.replace('%caseId%', caseId)
				.replace('%user%', user)
				.replace('%site%', siteName)
			;

		let body = message + "\r\n\r\n\r\n" + subject + ' at' + "\r\n -> " + location.href;

		body = encodeURIComponent(body).replace(':', '%3A');

		var mailLink = 'mailto:%email%?cc=%cc%&subject=%subject%&body=%body%'
			.replace('%email%', email)
			.replace('%cc%', emailCc)
			.replace('%subject%', encodeURIComponent(subject))
			.replace('%body%', body);

		var whatsappLink = div.data('whatsapp') + encodeURIComponent(
			message + ",\r\n" + ' at: ' +
			location.href + ' (' + siteName + ')').replace(':', '%3A');
		console.info(whatsappLink);

		$('.send-via-email', div).attr('href', mailLink);
		$('.send-via-whatsapp', div).attr('href', whatsappLink);

		$('.send-via-whatsapp.send-icon, .send-via-email.send-icon', div).removeClass('disabled').removeClass('bg-dark-subtle');
		$('.send-via-whatsapp.send-icon', div).addClass('bg-whatsapp');
		$('.send-via-email.send-icon', div).addClass('bg-info');
	}

	function checkboxAdd(ix, el) {
		el = $(el);
		el.html('<label>' + el.html() + '</label>');

		const wantsOpen = el.html().includes('<!--open-->');
		const wantsLarge = el.html().includes('<!--large-->');

		$('<input class="form-check-input" type="checkbox" ' +
			(wantsOpen ? ' checked readonly disabled' : '') +' />')
			.on('change', checkboxToggle)
			.prependTo($('label', el));

			//registers auto height in document.ready's divs.each
		$('<br/><textarea class="form-control w-100"' +
			(wantsOpen ? '' : 'style="display: none;" ') + ' rows="' +
			(wantsLarge ? '3' : '1') + '"></textarea>').appendTo(el);
	}

	function textAreaAutoHeight(ev) {
		this.style.height = '1px';
		this.style.height = this.scrollHeight + 'px';
	}

	function checkboxToggle(ev) {
		if (event.originalEvent && $(event.originalEvent.target).closest('a').length) return;
		const txt = $('input[type=text], textarea', $(this).closest('li'));
		if($(this).is(':checked')) txt.show(); else txt.hide();
	}
});
