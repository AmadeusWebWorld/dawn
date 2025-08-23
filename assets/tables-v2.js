if (typeof ($) === 'undefined') $ = jQuery.noConflict();

$(document).ready(function () {
	if ($('.amadeus-data-table').length == 0) return;

	function thisTable(el) {
		return el.closest('.amadeus-data-table');
	}

	$('.amadeus-data-table').DataTable({
		amadeusTable: undefined, id: undefined, cardView: undefined,

		//https://datatables.net/reference/option/layout
		layout: {
			top: 'info',
			topStart: null,
			topEnd: {
				search: {
					placeholder: 'Search'
				}
			},
			bottom: { buttons: ['copy', 'pdf', 'print'] },
			bottomStart: null,
			bottomEnd: null,
		},

		responsive: true,
		paging: false,

		initComplete: function (settings, json) {
			const amadeusTable = thisTable($(this))
				tableId = amadeusTable.attr('id'),
				cardView = tableId + '-card-view';

			// Setup - add a text input to each header cell with header name
			$('thead th', amadeusTable).each(function (ix, el) {
				var title = $(this).text();
				$(this).html(title + '<br><input class="filter filter-' + title.toLowerCase().replaceAll(' ', '-') + '" type="text" placeholder="Search ' + title + '" />');
			});

			const table = this.api();

			// Apply the search
			table.columns().every(function () {
				var that = this;
				//TODO: responsive shows as undefined
				$('input', this.header()).on('keyup change clear', function () {
					if (that.search() !== this.value) {
						that
							.search(this.value)
							.draw();
					}
				});
			});
		},
	});
});
