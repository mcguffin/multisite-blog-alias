import $ from 'jquery';

$(document)
	// .on('click','.admin-domain-alias a.dashicons', e => {
	// 	e.preventDefault();
	// 	const $notice = $(e.target).next('.notice');
	// 	if ( $notice.is(':visible') ) {
	// 		$notice.hide();
	// 	} else {
	// 		$notice.show();
	// 	}
	// } )
	.on('click','.admin-domain-alias [data-action="check-alias-status"]', e => {
		e.preventDefault();
		$(e.target).closest('table').addClass('has-status');
		$('[data-check-status]').each( (i,el) => {
			const url = $(el).attr('data-check-status');

			$(el).html('<span class="spinner is-active"></span>');
			const req = $.get(url).done( resp => {
				$(el).html( req.responseText );
			});
		});
	} )

	;
