@php
	// Categories' Jobs Pages
	$noIndexCategoriesPermalinkPages ??= false;
	$noIndexCategoriesQueryStringPages ??= false;
	
	// Cities' Jobs Pages
	$noIndexCitiesPermalinkPages ??= false;
	$noIndexCitiesQueryStringPages ??= false;
	
	// Users' Jobs Pages
	$noIndexUsersByIdPages ??= false;
	$noIndexUsersByUsernamePages ??= false;
	
	// Tags' Jobs Pages
	$noIndexTagsPages ??= false;
	
	// Companies' Jobs Pages
	$noIndexCompaniesPages ??= false;
	
	// Filters (and Orders) on Jobs Pages (Except Pagination)
	$noIndexFiltersOnEntriesPages ??= false;
	
	// "No result" Pages (Empty Searches Results Pages)
	$noIndexNoResultPages ??= false;
	
	// Jobs Report Pages
	$noIndexListingsReportPages ??= false;
	
	// All Website Pages
	$noIndexAllPages = (config('settings.seo.no_index_all'));
@endphp
@if (
		$noIndexAllPages
		|| $noIndexCategoriesPermalinkPages
		|| $noIndexCategoriesQueryStringPages
		|| $noIndexCitiesPermalinkPages
		|| $noIndexCitiesQueryStringPages
		|| $noIndexUsersByIdPages
		|| $noIndexUsersByUsernamePages
		|| $noIndexCompaniesPages
		|| $noIndexTagsPages
		|| $noIndexFiltersOnEntriesPages
		|| $noIndexNoResultPages
		|| $noIndexListingsReportPages
	)
	<meta name="robots" content="noindex,nofollow">
	<meta name="googlebot" content="noindex">
@endif
