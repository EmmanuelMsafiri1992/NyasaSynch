{{--
 * Nyasajob - Job Board Web Application
 * Copyright (c) BeDigit. All Rights Reserved
 *
 * Website: https://laraclassifier.com/nyasajob
 * Author: BeDigit | https://bedigit.com
 *
 * LICENSE
 * -------
 * This software is furnished under a license and may be used and copied
 * only in accordance with the terms of such license and with the inclusion
 * of the above copyright notice. If you Purchased from CodeCanyon,
 * Please read the full License from here - https://codecanyon.net/licenses/standard
--}}
@extends('layouts.master')

@php
	$post ??= [];
	$catBreadcrumb ??= [];
	$topAdvertising ??= [];
	$bottomAdvertising ??= [];
@endphp

@section('content')
	@if (session()->has('flash_notification'))
		@includeFirst([config('larapen.core.customizedViewPath') . 'common.spacer', 'common.spacer'])
		@php
			$paddingTopExists = true;
		@endphp
		<div class="container">
			<div class="row">
				<div class="col-12">
					@include('flash::message')
				</div>
			</div>
		</div>
		@php
			session()->forget('flash_notification.message');
		@endphp
	@endif
	
	{{-- Archived listings message --}}
	@if (!empty(data_get($post, 'archived_at')))
		@includeFirst([config('larapen.core.customizedViewPath') . 'common.spacer', 'common.spacer'])
		@php
			$paddingTopExists = true;
		@endphp
		<div class="container">
			<div class="row">
				<div class="col-12">
					<div class="alert alert-warning" role="alert">
						{!! t('This ad has been archived') !!}
					</div>
				</div>
			</div>
		</div>
	@endif
	
	<div class="main-container">
		
		@if (!empty($topAdvertising))
			@includeFirst(
				[config('larapen.core.customizedViewPath') . 'layouts.inc.advertising.top', 'layouts.inc.advertising.top'],
				['paddingTopExists' => $paddingTopExists ?? false]
			)
			@php
				$paddingTopExists = false;
			@endphp
		@endif

		<div class="container {{ (!empty($topAdvertising)) ? 'mt-3' : 'mt-2' }}">
			<div class="row">
				<div class="col-md-12">
					
					<nav aria-label="breadcrumb" role="navigation" class="float-start">
						<ol class="breadcrumb">
							<li class="breadcrumb-item"><a href="{{ url('/') }}"><i class="fa-solid fa-house"></i></a></li>
							<li class="breadcrumb-item"><a href="{{ url('/') }}">{{ config('country.name') }}</a></li>
							@if (is_array($catBreadcrumb) && count($catBreadcrumb) > 0)
								@foreach($catBreadcrumb as $key => $value)
									<li class="breadcrumb-item">
										<a href="{{ $value->get('url') }}">
											{!! $value->get('name') !!}
										</a>
									</li>
								@endforeach
							@endif
							<li class="breadcrumb-item active">{{ str(data_get($post, 'title'))->limit(70) }}</li>
						</ol>
					</nav>
					
					<div class="float-end backtolist">
						<a href="{{ rawurldecode(url()->previous()) }}">
							<i class="fa-solid fa-angles-left"></i> {{ t('back_to_results') }}
						</a>
					</div>
					
				</div>
			</div>
		</div>
		
		<div class="container">
			<div class="row">
				<div class="col-lg-9 page-content col-thin-right">
					<div class="inner inner-box items-details-wrapper pb-0">
						<h1 class="h4 fw-bold enable-long-words">
							<strong>
                                <a href="{{ \App\Helpers\UrlGen::post($post) }}" title="{{ data_get($post, 'title') }}">
                                    {{ data_get($post, 'title') }}
                                </a>
                            </strong>
							<small class="label label-default adlistingtype">
								{{ t('_type_job', ['type' => data_get($post, 'postType.name')]) }}
							</small>
							@if (data_get($post, 'featured') == 1 && !empty(data_get($post, 'payment.package')))
								&nbsp;<i class="fa-solid fa-circle-check"
								   style="color: {{ data_get($post, 'payment.package.ribbon') }};"
								   data-bs-placement="bottom"
								   data-bs-toggle="tooltip"
								   title="{{ data_get($post, 'payment.package.short_name') }}"
								></i>
							@endif
						</h1>
						<span class="info-row">
							@if (!config('settings.listing_page.hide_date'))
							<span class="date"{!! (config('lang.direction')=='rtl') ? ' dir="rtl"' : '' !!}>
								<i class="fa-regular fa-clock"></i> {!! data_get($post, 'created_at_formatted') !!}
							</span>&nbsp;
							@endif
							<span class="category"{!! (config('lang.direction')=='rtl') ? ' dir="rtl"' : '' !!}>
								<i class="bi bi-folder"></i> {{ data_get($post, 'category.parent.name', data_get($post, 'category.name')) }}
							</span>&nbsp;
							<span class="item-location"{!! (config('lang.direction')=='rtl') ? ' dir="rtl"' : '' !!}>
								<i class="bi bi-geo-alt"></i> {{ data_get($post, 'city.name') }}
							</span>&nbsp;
							<span class="category"{!! (config('lang.direction')=='rtl') ? ' dir="rtl"' : '' !!}>
								<i class="bi bi-eye"></i> {{ data_get($post, 'visits_formatted') }}
							</span>
							<span class="category float-md-end"{!! (config('lang.direction')=='rtl') ? ' dir="rtl"' : '' !!}>
								{{ t('reference') }}: {{ data_get($post, 'reference') }}
							</span>
						</span>
						
						@includeFirst([config('larapen.core.customizedViewPath') . 'post.show.inc.details', 'post.show.inc.details'])
					</div>
				</div>
				
				<div class="col-lg-3 page-sidebar-right">
					@includeFirst([config('larapen.core.customizedViewPath') . 'post.show.inc.sidebar', 'post.show.inc.sidebar'])
				</div>
			</div>

		</div>
		
		@if (config('settings.listing_page.similar_listings') == '1' || config('settings.listing_page.similar_listings') == '2')
			@php
				$widgetType = (config('settings.listing_page.similar_listings_in_carousel') ? 'carousel' : 'normal');
			@endphp
			@includeFirst([
					config('larapen.core.customizedViewPath') . 'search.inc.posts.widget.' . $widgetType,
					'search.inc.posts.widget.' . $widgetType
				],
				['widget' => ($widgetSimilarPosts ?? null), 'firstSection' => false]
			)
		@endif
		
		@includeFirst(
			[config('larapen.core.customizedViewPath') . 'layouts.inc.advertising.bottom', 'layouts.inc.advertising.bottom'],
			['firstSection' => false]
		)
		
		@if (isVerifiedPost($post))
			@includeFirst(
				[config('larapen.core.customizedViewPath') . 'layouts.inc.tools.facebook-comments', 'layouts.inc.tools.facebook-comments'],
				['firstSection' => false]
			)
		@endif
		
	</div>
@endsection
@php
	if (!session()->has('emailVerificationSent') && !session()->has('phoneVerificationSent')) {
		if (session()->has('message')) {
			session()->forget('message');
		}
	}
@endphp

@section('modal_message')
	@if (auth()->check() || config('settings.listing_page.guest_can_contact_authors')=='1')
		@includeFirst([config('larapen.core.customizedViewPath') . 'account.messenger.modal.create', 'account.messenger.modal.create'])
	@endif
@endsection

@section('after_styles')
@endsection

@section('after_scripts')
    @if (config('services.googlemaps.key'))
		{{-- More Info: https://developers.google.com/maps/documentation/javascript/versions --}}
        <script async src="https://maps.googleapis.com/maps/api/js?v=weekly&key={{ config('services.googlemaps.key') }}"></script>
    @endif
	
	<script>
		{{-- Favorites Translation --}}
		var lang = {
            labelSavePostSave: "{!! t('Save Job') !!}",
            labelSavePostRemove: "{{ t('Saved Job') }}",
            loginToSavePost: "{!! t('Please log in to save the Ads') !!}",
            loginToSaveSearch: "{!! t('Please log in to save your search') !!}"
        };
		
		onDocumentReady((event) => {
			@if (config('settings.listing_page.show_listing_on_googlemap'))
				{{--
				let mapUrl = '{{ addslashes($mapUrl) }}';
				let iframe = document.getElementById('googleMaps');
				iframe.setAttribute('src', mapUrl);
				--}}
			@endif
		});
	</script>
@endsection
