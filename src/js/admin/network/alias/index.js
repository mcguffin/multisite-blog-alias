import $ from 'jquery';

$(document).on('click','.admin-domain-alias a.dashicons', e => {
	const $notice = $(e.target).next('.notice');
	if ( $notice.is(':visible') ) {
		$notice.hide();
	} else {
		$notice.show();
	}
} );