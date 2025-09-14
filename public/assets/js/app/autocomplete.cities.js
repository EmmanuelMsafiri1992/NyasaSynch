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

if (typeof fakeLocationsResults === 'undefined') {
	var fakeLocationsResults = '0';
}
if (typeof errorText === 'undefined') {
	var errorText = {
		errorFound: 'Error Found'
	};
}
if (typeof isLoggedAdmin === 'undefined') {
	isLoggedAdmin = false;
}

/*
 * Check if "No Results" can be shown or not
 * -----
 * NOTE:
 * Typically, don't display "No results found" when the app can:
 * - Use the most populate city when searched city cannot be found
 * - City filter can be ignored when searched city cannot be found
 *
 * For more information, check out the:
 * Admin panel → Settings → General → Listings List → Fake locations results
 */
const showNoSuggestionNotice = (['1', '2'].indexOf(fakeLocationsResults) === -1);

// Auto Complete Global Parameters
const inputElSelector = "input#locSearch";
const threshold = 1;
const suggestionsElSelectorId = "autoCompleteResults";
const suggestionsZIndex = 1492;

/*
 * Documentation: https://tarekraafat.github.io/autoComplete.js/
 */
onDocumentReady(function (event) {
	if (!isString(countryCode) || countryCode === '0' || countryCode === '') {
		return false;
	}
	
	// Handle Inputs
	const inputEl = document.querySelector(inputElSelector);
	const lSearchEl = document.getElementById("lSearch");
	const rSearchEl = document.getElementById("rSearch");
	const _tokenEl = document.querySelector("input[name=_token]");
	const tooltipTriggerEl = document.querySelector("#locSearch.tooltipHere");
	
	if (!isDomElement(inputEl) || !isDomElement(lSearchEl)) {
		return false;
	}
	
	// Empty the hidden fields when the 'inputEl' value is empty
	addInputChangeListeners(inputEl, function (event) {
		const liveInputEl = event.target;
		emptyHiddenFields(liveInputEl, lSearchEl, rSearchEl);
	});
	
	// AutoComplete Configuration
	let config = {
		selector: inputElSelector,
		wrapper: false,
		threshold: 0,
		debounce: 500, /* in milliseconds */
		data: {
			src: (query) => {
				// Before AJAX Request ===================================================
				disableTooltipForElement(tooltipTriggerEl);
				
				/* Clear all whitespaces from the 'query' when it does not contain any other type of character */
				let tmpQuery = query.replace(/ /g, "");
				query = (tmpQuery.length <= 0) ? tmpQuery : query;
				
				/* Empty the 'query' when its length is < 'threshold' */
				query = (query.length >= threshold) ? query : "";
				
				if (query.length < threshold) {
					return [];
				}
				
				const liveSuggestionsEl = document.getElementById(suggestionsElSelectorId);
				displayLoadingMessage(liveSuggestionsEl);
				
				// Make AJAX Request =====================================================
				let url = `${siteUrl}/ajax/countries/${strToLower(countryCode)}/cities/autocomplete`;
				let data = {
					"query": query,
					"_token": _tokenEl.value
				};
				
				return httpRequest("post", url, data)
				.then((json) => {
					if (!isDefined(json.suggestions)) {
						let message = 'The "json.suggestions" property is undefined!';
						jsAlert(message, "error", false);
						
						return [];
					}
					
					return json.suggestions;
				}).catch((error) => {
					jsAlert(error, "error", false, true);
				});
			},
			keys: ['name'], /* Data source 'Object' key to be searched */
			cache: false,   /* Never set to 'true' */
		},
		resultsList: {
			tag: "ul",
			id: suggestionsElSelectorId,
			class: "auto-complete-results dropdown mt-0 p-0 shadow bg-body z-3",
			destination: "body",
			position: "beforeend",
			maxResults: undefined,
			noResults: true,
			element: (list, data) => {
				const query = data.query;
				const results = data.results;
				
				const liveInputEl = document.querySelector(inputElSelector);
				adjustSuggestionsElStyle(liveInputEl, list);
				
				hideSuggestionsElWhenAreaTextIsFilled(list, results, query);
				if (results.length <= 0) {
					displayNoResultsMessage(list, results, query, threshold);
				}
			},
		},
		resultItem: {
			class: "auto-complete-item px-3 py-2",
			element: (item, data) => {
				const json = data.value;
				const formattedLabel = data.match;
				
				redrawItemElement(item, json, formattedLabel);
			},
			highlight: "auto-complete-item-highlight",
			selected: "auto-complete-item-selected bg-light-inverse"
		},
		events: {
			input: {
				focus: (event) => {
					const inputValue = autoCompleteJS.input.value;
					if (inputValue.length >= threshold) {
						autoCompleteJS.open();
					} else {
						/*
						 * Start will call the data.src => () method,
						 * that will return an empty array,
						 * that will call the resultsList.element => () method
						 * that will call the displayNoResultsMessage() function
						 */
						autoCompleteJS.start();
					}
				},
				selection: (event) => selectElement(event, inputEl, lSearchEl, tooltipTriggerEl)
			}
		},
	};
	
	// Apply the AutoComplete Config
	const autoCompleteJS = new autoComplete(config);
	
	// Open Event
	inputEl.addEventListener("open", (event) => addOpenAutoCompleteListener(event, autoCompleteJS));
	
});

/**
 * Add open autocomplete event to the DOM element listener
 * @param event
 * @param autoCompleteJS
 */
function addOpenAutoCompleteListener(event, autoCompleteJS) {
	if (isEmpty(event.detail)) {
		const inputEl = event.target;
		if (!isEmpty(inputEl.value)) {
			autoCompleteJS.start(inputEl.value);
		}
	}
}

/**
 * Display loading message
 * @param listEl
 * @returns {boolean}
 */
function displayLoadingMessage(listEl) {
	if (!isDomElement(listEl)) {
		return false;
	}
	
	const liveInputEl = document.querySelector(inputElSelector);
	adjustSuggestionsElStyle(liveInputEl, listEl);
	
	const message = `<i class="bi bi-hourglass-split"></i> ${langLayout.loading}`;
	createFakeElementInList(listEl, message);
}

/**
 * Adjust the suggestions element position and coordinates
 * @param inputEl
 * @param listEl
 */
function adjustSuggestionsElStyle(inputEl, listEl) {
	if (!isDomElement(inputEl) || !isDomElement(listEl)) {
		return false;
	}
	
	/*
	 * jQuery-AutoComplete (inline style emulator)
	 * Apply the style below using JavaScript
	 * "position: absolute; max-height: 320px; z-index: 1492; top: 366.148px; left: 914.914px; width: 353.414px;"
	 *
	 * NOTE:
	 * - Set position from "fixed" to "absolute" and no longer need update coordinates on scroll
	 * - Position need to be set to "fixed" in CSS
	 * - The listEl need to inserted as last DOM elements (or as the last element)
	 */
	const coords = getElementCoords(inputEl);
	if (isEmpty(coords)) {
		return false;
	}
	
	listEl.style.position = "absolute";
	listEl.style.width = coords.width + "px";
	listEl.style.top = coords.bottom + "px"; // Position immediately below
	listEl.style.left = coords.left + "px";  // Same left position
	listEl.style.zIndex = suggestionsZIndex;
}

/**
 * Hide results list when region keyword search trigger is filled
 * @param listEl
 * @param results
 * @param query
 * @returns {boolean}
 */
function hideSuggestionsElWhenAreaTextIsFilled(listEl, results, query) {
	if (!isDomElement(listEl) || !isDefined(results) || !isDefined(query)) {
		return false;
	}
	
	const areaText = langLayout.location.area;
	const queryExtractedText = query.substring(0, areaText.length);
	
	if (results.length <= 0) {
		if (queryExtractedText === areaText) {
			listEl.classList.add('d-none');
			listEl.innerHTML = "";
		} else {
			listEl.classList.remove('d-none');
		}
	} else {
		listEl.classList.remove('d-none');
	}
}

/**
 * Redraw each results list item
 * @param itemEl
 * @param json
 * @param formattedLabel
 */
function redrawItemElement(itemEl, json, formattedLabel = null) {
	const adminName = isDefined(json.admin) ? ', ' + json.admin : '';
	const icon = `<i class="bi bi-geo-alt text-secondary"></i>`;
	
	formattedLabel = !isEmpty(formattedLabel) ? formattedLabel : json.name;
	formattedLabel = formattedLabel + adminName;
	formattedLabel = icon + ' ' + formattedLabel;
	formattedLabel = `<span class="text-truncate">${formattedLabel}</span>`;
	
	itemEl.innerHTML = formattedLabel;
}

/**
 * Display no results message
 * @param listEl
 * @param results
 * @param query
 * @param threshold
 * @returns {boolean}
 */
function displayNoResultsMessage(listEl, results, query, threshold) {
	let message;
	if (query.length <= 0) {
		message = langLayout.autoComplete.searchCities;
	} else if (query.length > 0 && query.length < threshold) {
		message = langLayout.autoComplete.enterMinimumChars(threshold);
	} else {
		if (!showNoSuggestionNotice) {
			listEl.classList.add('d-none');
			return false;
		}
		message = langLayout.autoComplete.noResultsFor(query);
	}
	message = `<span>${message}</span>`;
	
	createFakeElementInList(listEl, message);
}

/**
 * Create fake element in the results list wrapper
 * @param listEl
 * @param content
 */
function createFakeElementInList(listEl, content) {
	const divEl = document.createElement("div");
	divEl.setAttribute("class", "auto-complete-no-results px-3 py-2 text-left");
	divEl.innerHTML = content;
	
	listEl.innerHTML = "";
	listEl.prepend(divEl);
}

/**
 * Select a results list's element
 * @param event
 * @param inputEl
 * @param lSearchEl
 * @param tooltipTriggerEl
 */
function selectElement(event, inputEl, lSearchEl, tooltipTriggerEl) {
	const selection = event.detail.selection;
	const item = selection.value;
	
	const adminName = isDefined(item.admin) ? ', ' + item.admin : '';
	const cityId = item.id;
	const cityName = item.name + adminName;
	
	lSearchEl.value = cityId;
	inputEl.value = cityName;
	
	inputEl.blur();
	
	enableTooltipForElement(tooltipTriggerEl);
}

/**
 * Check if the autocomplete field is empty when its value change, and if so, empty the hidden fields
 * @param inputEl
 * @param lSearchEl
 * @param rSearchEl
 */
function emptyHiddenFields(inputEl, lSearchEl, rSearchEl = null) {
	if (!isDomElement(inputEl)) {
		return false;
	}
	
	if (isEmpty(inputEl.value)) {
		lSearchEl.value = '';
		if (isElDefined(rSearchEl)) {
			rSearchEl.value = '';
		}
	}
}
