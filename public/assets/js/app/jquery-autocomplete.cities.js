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

if (typeof noResultsText === 'undefined') {
	var noResultsText = 'No results';
	if (
		typeof langLayout.select2 !== 'undefined'
		&& typeof langLayout.select2.noResults !== 'undefined'
		&& typeof langLayout.select2.noResults === 'function'
	) {
		noResultsText = langLayout.select2.noResults();
	}
}
if (typeof fakeLocationsResults === 'undefined') {
	var fakeLocationsResults = '0';
}
if (typeof isLoggedAdmin === 'undefined') {
	isLoggedAdmin = false;
}
if (typeof errorText === 'undefined') {
	var errorText = {
		errorFound: 'Error found'
	};
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
const suggestionsZIndex = 1492;

/*
 * Documentation: https://github.com/devbridge/jQuery-Autocomplete
 */
onDocumentReady((event) => {
	
	const tooltipTriggerEl = document.querySelector("#locSearch.tooltipHere");
	
	if (isString(countryCode) && countryCode !== '0' && countryCode !== '') {
		noResultsText = '<div class="p-2">' + noResultsText + '</div>';
		
		const locSearchEl = $(inputElSelector);
		
		// AutoComplete Configuration
		let options = {
			zIndex: suggestionsZIndex,
			maxHeight: 333,
			serviceUrl: siteUrl + '/ajax/countries/' + strToLower(countryCode) + '/cities/autocomplete',
			type: 'post',
			data: {
				'city': $(this).val(),
				'_token': $('input[name=_token]').val()
			},
			minChars: threshold,
			showNoSuggestionNotice: showNoSuggestionNotice,
			noSuggestionNotice: noResultsText,
			onSearchStart: function (params) {
				disableTooltipForElement(tooltipTriggerEl);
			},
			transformResult: function (response, originalQuery) {
				response = $.parseJSON(response);
				
				let suggestions = $.map(response.suggestions, function (dataItem) {
					let adminName = isDefined(dataItem.admin) ? ', ' + dataItem.admin : '';
					let cityName = dataItem.name + adminName;
					
					return {
						data: dataItem.id,
						value: cityName
					};
				});
				
				return {suggestions: suggestions};
			},
			beforeRender: function (container, suggestions) {
				const query = $('input#locSearch').val();
				const suggestionsEl = $('.autocomplete-suggestions');
				hideResultsListWhenAreaTextIsFilledJQuery(suggestionsEl, suggestions, query);
			},
			formatResult: function (suggestion, currentValue) {
				const icon = `<i class="bi bi-geo-alt text-secondary"></i>`;
				const formattedLabel = $.Autocomplete.defaults.formatResult(suggestion, currentValue);
				
				return icon + ' ' + formattedLabel;
			},
			onSearchError: function (query, xhr, textStatus, errorThrown) {
				bsModalAlert(xhr, errorThrown);
			},
			onSelect: function (suggestion) {
				$('#lSearch').val(suggestion.data);
				enableTooltipForElement(tooltipTriggerEl);
			}
		};
		
		// Apply the AutoComplete Config
		// locSearchEl.devbridgeAutocomplete(options);
	}
	
});

function hideResultsListWhenAreaTextIsFilledJQuery(listEl, results, query) {
	if (typeof results === 'undefined' || typeof query === 'undefined') {
		return false;
	}
	
	const areaText = langLayout.location.area;
	const queryExtractedText = query.substring(0, areaText.length);
	
	if (results.length <= 0) {
		if (queryExtractedText === areaText) {
			listEl.addClass('d-none');
		} else {
			listEl.removeClass('d-none');
		}
	} else {
		listEl.removeClass('d-none');
	}
}
