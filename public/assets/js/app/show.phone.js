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
	
	const phoneBlockEls = document.querySelectorAll('.phoneBlock');
	if (phoneBlockEls.length > 0) {
		phoneBlockEls.forEach((element) => {
			element.addEventListener('click', (e) => {
				e.preventDefault(); /* Prevents submission or reloading */
				
				return showPhone(e.target);
			});
		});
	}
	
});

/**
 * Show the Contact's phone
 * @returns {boolean}
 */
async function showPhone(el) {
	if (el.tagName.toLowerCase() === 'i') {
		el = el.parentElement;
	}
	
	const postId = el.dataset.postId ?? 0;
	
	// When cache is true, the postId is updated to 0 after the first HTTP request
	// to prevent any other one, since the DOM has been updated
	const resultCanBeCached = true;
	
	// Use the cache and open the modal without making an HTTP request
	if (resultCanBeCached) {
		if (postId === 0 || postId === '0' || postId === '') {
			return false;
		}
	}
	
	const iconEl = el.querySelector('i');
	
	let url = siteUrl + '/ajax/post/phone';
	let _tokenEl = document.querySelector('input[name=_token]');
	let data = {
		'post_id': postId,
		'_token': _tokenEl.value ?? null
	};
	
	try {
		/* Change the button indicator */
		if (iconEl) {
			iconEl.classList.remove('fa-solid', 'fa-mobile-screen-button');
			iconEl.classList.add('spinner-border', 'spinner-border-sm');
			iconEl.style.verticalAlign = 'middle';
			iconEl.setAttribute('role', 'status');
			iconEl.setAttribute('aria-hidden', 'true');
		}
		
		const json = await httpRequest('POST', url, data);
		
		if (typeof json.phone === 'undefined') {
			return false;
		}
		
		el.innerHTML = '<i class="fa-solid fa-mobile-screen-button"></i> ' + json.phone;
		el.setAttribute('href', json.link);
		
		/* Disable Tooltip */
		let tooltip = bootstrap.Tooltip.getInstance(el);
		if (tooltip) {
			tooltip.dispose();
		}
		
		// If cache is activated, update the postId to 0
		if (resultCanBeCached) {
			el.dataset.postId = '0';
		}
		
	} catch (error) {
		let message = getErrorMessage(error);
		if (message !== null) {
			jsAlert(message, 'error');
		}
	}
	
	return false;
}
