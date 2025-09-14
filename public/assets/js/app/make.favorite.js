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

if (typeof isLogged === 'undefined') {
	var isLogged = false;
}

onDocumentReady((event) => {
	
	/* Save the Post */
	const makeFavoriteEls = document.querySelectorAll('.make-favorite, .save-job, a.saved-job');
	if (makeFavoriteEls.length > 0) {
		makeFavoriteEls.forEach((element) => {
			element.addEventListener('click', (event) => {
				event.preventDefault(); /* Prevents submission or reloading */
				
				if (isLogged !== true) {
					openLoginModal();
					return false;
				}
				
				savePost(event.target);
			});
		});
	}
	
	/* Save the Search */
	const saveSearchEl = document.getElementById('saveSearch');
	if (saveSearchEl) {
		saveSearchEl.addEventListener('click', (event) => {
			event.preventDefault(); /* Prevents submission or reloading */
			
			if (isLogged !== true) {
				openLoginModal();
				return false;
			}
			
			saveSearch(event.target);
		});
	}
	
});

/**
 * Save Ad
 * @param el
 * @returns {boolean}
 */
async function savePost(el) {
	if (el.tagName.toLowerCase() === 'span') {
		el = el.parentElement;
	}
	
	/* Get element's icon */
	let iconEl = null;
	if (el.tagName.toLowerCase() === 'a') {
		iconEl = el.querySelector('span') || el.querySelector('i');
	}
	
	const postId = el.closest('li').id;
	const url = siteUrl + '/ajax/save/post';
	const _tokenEl = document.querySelector('input[name=_token]');
	const data = {
		'post_id': postId,
		'_token': _tokenEl.value ?? null
	};
	
	if (iconEl) {
		iconEl.classList.remove('fa-regular', 'fa-bookmark');
		iconEl.classList.add('spinner-border', 'spinner-border-sm');
		iconEl.style.verticalAlign = 'middle';
		iconEl.setAttribute('role', 'status');
		iconEl.setAttribute('aria-hidden', 'true');
	}
	
	try {
		const json = await httpRequest('POST', url, data);
		
		if (json.isLogged === undefined) {
			if (iconEl) {
				iconEl.classList.remove('spinner-border', 'spinner-border-sm');
				iconEl.style.verticalAlign = '';
				iconEl.classList.add('fa-regular', 'fa-bookmark');
				iconEl.removeAttribute('role');
				iconEl.removeAttribute('aria-hidden');
			}
			return false;
		}
		
		const isNotLogged = (json.isLogged !== true);
		const isUnauthorized = (json.status && (json.status === 401 || json.status === 419));
		
		if (isNotLogged || isUnauthorized) {
			/* Reset the button indicator */
			if (iconEl) {
				iconEl.classList.remove('spinner-border', 'spinner-border-sm');
				iconEl.style.verticalAlign = '';
				iconEl.classList.add('fa-regular', 'fa-bookmark');
				iconEl.removeAttribute('role');
				iconEl.removeAttribute('aria-hidden');
			}
			
			openLoginModal();
			
			if (json.message) {
				jsAlert(json.message, 'error', false);
			}
			
			return false;
		}
		
		if (json.isSaved === true) {
			if (el.classList.contains('btn')) {
				const saveBtnEl = document.getElementById(json.postId);
				saveBtnEl.classList.add('saved-job');
				
				const saveBtnLinkEl = document.querySelector(`#${json.postId} a`);
				saveBtnLinkEl.classList.add('saved-job');
			} else {
				el.innerHTML = `<span class="fa-solid fa-bookmark"></span> ${lang.labelSavePostRemove}`;
			}
			jsAlert(json.message, 'success');
		} else {
			if (el.classList.contains('btn')) {
				const saveBtnEl = document.getElementById(json.postId);
				saveBtnEl.classList.remove('saved-job');
				
				const saveBtnLinkEl = document.querySelector(`#${json.postId} a`);
				saveBtnLinkEl.classList.remove('saved-job');
			} else {
				el.innerHTML = `<span class="fa-regular fa-bookmark"></span> ${lang.labelSavePostSave}`;
			}
			jsAlert(json.message, 'success');
		}
		
		if (iconEl) {
			iconEl.classList.remove('spinner-border', 'spinner-border-sm');
			iconEl.style.verticalAlign = '';
			iconEl.classList.add('fa-regular', 'fa-bookmark');
			iconEl.removeAttribute('role');
			iconEl.removeAttribute('aria-hidden');
		}
		
	} catch (error) {
		if (iconEl) {
			iconEl.classList.remove('spinner-border', 'spinner-border-sm');
			iconEl.style.verticalAlign = '';
			iconEl.classList.add('fa-regular', 'fa-bookmark');
			iconEl.removeAttribute('role');
			iconEl.removeAttribute('aria-hidden');
		}
		
		if (error.response && error.response.status) {
			const response = error.response;
			if (response.status === 401 || response.status === 419) {
				/*
				 * Since the modal login code is injected only for guests,
				 * the line below can be fired only for guests (i.e. when user is not logged in)
				 */
				openLoginModal();
				
				if (!isLogged) {
					return false;
				}
			}
		}
		
		const message = getErrorMessage(error);
		if (message !== null) {
			jsAlert(message, 'error', false);
		}
	}
	
	return false;
}

/**
 * Save Search
 * @param el
 * @returns {boolean}
 */
async function saveSearch(el) {
	if (el.tagName.toLowerCase() === 'i') {
		el = el.parentElement;
	}
	
	let searchUrl = el.dataset.searchUrl;
	let resultsCount = el.dataset.resultsCount;
	
	if (!searchUrl) {
		console.error("Search URL not found.");
		return false;
	}
	
	let url = siteUrl + '/ajax/save/search';
	const _tokenEl = document.querySelector('input[name=_token]');
	const data = {
		'url': searchUrl,
		'results_count': resultsCount,
		'_token': _tokenEl.value ?? null
	};
	
	try {
		const json = await httpRequest('POST', url, data);
		
		if (typeof json.isLogged === 'undefined') {
			return false;
		}
		
		if (json.isLogged !== true) {
			openLoginModal();
			return false;
		}
		
		/* Logged Users - Notification */
		jsAlert(json.message, 'success');
	} catch (error) {
		if (error.response && error.response.status) {
			const response = error.response;
			if (response.status === 401 || response.status === 419) {
				openLoginModal();
				return false;
			}
		}
		
		const message = getErrorMessage(error);
		if (message !== null) {
			jsAlert(message, 'error', false);
		}
	}
	
	return false;
}
