/*
 * JobClass - Job Board Web Application
 * Copyright (c) BeDigit. All Rights Reserved
 *
 * Website: https://laraclassifier.com/jobclass
 * Author: BeDigit | https://bedigit.com
 *
 * LICENSE
 * -------
 * This software is furnished under a license and may be used and copied
 * only in accordance with the terms of such license and with the inclusion
 * of the above copyright notice. If you Purchased from CodeCanyon,
 * Please read the full License from here - https://codecanyon.net/licenses/standard
 */

onDocumentReady((event) => {
	
	let xhrOptions = {
		headers: {
			'X-Requested-With': 'XMLHttpRequest',
		},
		async: true,
		cache: true,
		xhrFields: {withCredentials: true},
		crossDomain: true
	};
	
	/* Ajax's calls should always have the CSRF token attached to them; otherwise they won't work */
	const metaTokenEl = document.querySelector('meta[name="csrf-token"]');
	if (metaTokenEl) {
		xhrOptions.headers['X-CSRF-TOKEN'] = metaTokenEl.getAttribute('content');
	}
	
	$.ajaxSetup(xhrOptions);
	
});
