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

@section('wizard')
    @includeFirst([
		config('larapen.core.customizedViewPath') . 'post.createOrEdit.multiSteps.inc.wizard',
		'post.createOrEdit.multiSteps.inc.wizard'
	])
@endsection

@php
	$packages ??= collect();
	$paymentMethods ??= collect();
	
	$selectedPackage ??= null;
	$currentPackagePrice = $selectedPackage->price ?? 0;
@endphp
@section('content')
	@includeFirst([config('larapen.core.customizedViewPath') . 'common.spacer', 'common.spacer'])
    <div class="main-container">
        <div class="container">
            <div class="row">
    
                @includeFirst([config('larapen.core.customizedViewPath') . 'post.inc.notification', 'post.inc.notification'])
                
                <div class="col-md-12 page-content">
                    <div class="inner-box">
						
                        <h2 class="title-2">
							<strong>
								@if (!empty($selectedPackage))
									<i class="fa-solid fa-wallet"></i> {{ t('Payment') }}
								@else
									<i class="fa-solid fa-tags"></i> {{ t('Pricing') }}
								@endif
							</strong>
	                        @php
		                        try {
									if (auth()->check()) {
										if (auth()->user()->can(\App\Models\Permission::getStaffPermissions())) {
											$postLink = '-&nbsp;<a href="' . \App\Helpers\UrlGen::post($post) . '"
													  class=""
													  data-bs-placement="top"
													  data-bs-toggle="tooltip"
													  title="' . $post->title . '"
											>' . str($post->title)->limit(45) . '</a>';
											
											echo $postLink;
										}
									}
								} catch (\Throwable $e) {}
	                        @endphp
						</h2>
						
                        <div class="row">
                            <div class="col-sm-12">
                                <form class="form" id="payableForm" method="POST" action="{{ url()->current() }}">
                                    {!! csrf_field() !!}
                                    <input type="hidden" name="payable_id" value="{{ $post->id }}">
                                    <fieldset>
										
										@if (!empty($selectedPackage))
											@includeFirst([
												config('larapen.core.customizedViewPath') . 'payment.packages.selected',
												'payment.packages.selected'
											])
										@else
											@includeFirst([
												config('larapen.core.customizedViewPath') . 'payment.packages',
												'payment.packages'
											])
										@endif
                                        
                                        {{-- Button  --}}
                                        <div class="row">
                                            <div class="col-md-12 text-center mt-4">
												<a id="skipBtn" href="{{ \App\Helpers\UrlGen::post($post) }}" class="btn btn-default btn-lg">
													{{ t('Skip') }}
												</a>
                                                <button id="payableFormSubmitButton" class="btn btn-success btn-lg payableFormSubmitButton"> {{ t('Pay') }} </button>
                                            </div>
                                        </div>
                                    
                                    </fieldset>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('after_styles')
@endsection

@section('after_scripts')
	<script>
		const packageType = 'promotion';
		const formType = 'multiStep';
		const isCreationFormPage = {{ request()->segment(2) == 'create' ? 'true' : 'false' }};
	</script>
	@include('common.js.payment-scripts')
	@include('common.js.payment-js')
@endsection
